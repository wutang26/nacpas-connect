<?php

namespace Tests\Feature;

use App\Jobs\ProcessNetworkCommand;
use App\Models\Customer;
use App\Models\NetworkCommand;
use App\Models\NetworkIntegration;
use App\Models\Organization;
use App\Models\Package;
use App\Models\Site;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WifiSession;
use App\Services\Network\NetworkCommandService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use Tests\TestCase;

class NetworkCommandServiceTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * Test that authorization creates only one command
     * and dispatches only one queued job for duplicate requests.
     */
    public function test_authorize_client_creates_one_command_and_dispatches_one_job(): void
    {
        Queue::fake();

        $fixture = $this->createFixture();

        $service = app(NetworkCommandService::class);

        $firstCommand = $service->authorizeClient(
            $fixture['integration'],
            $fixture['session']
        );

        $secondCommand = $service->authorizeClient(
            $fixture['integration'],
            $fixture['session']
        );

        $this->assertSame($firstCommand->id, $secondCommand->id);

        $this->assertDatabaseCount('network_commands', 1);

        $this->assertDatabaseHas('network_commands', [
            'id' => $firstCommand->id,
            'wifi_session_id' => $fixture['session']->id,
            'command_type' => 'authorize_client',
            'status' => 'pending',
        ]);

        Queue::assertPushed(ProcessNetworkCommand::class, 1);

        Queue::assertPushed(
            ProcessNetworkCommand::class,
            fn (ProcessNetworkCommand $job) =>
                $job->commandId === $firstCommand->id
        );
    }

    /**
     * Test that one WiFi session can have separate
     * authorization and disconnection commands.
     */
    public function test_authorize_and_disconnect_commands_are_separate(): void
    {
        Queue::fake();

        $fixture = $this->createFixture();

        $service = app(NetworkCommandService::class);

        $authorizeCommand = $service->authorizeClient(
            $fixture['integration'],
            $fixture['session']
        );

        $disconnectCommand = $service->disconnectClient(
            $fixture['integration'],
            $fixture['session']
        );

        $this->assertNotSame(
            $authorizeCommand->id,
            $disconnectCommand->id
        );

        $this->assertDatabaseCount('network_commands', 2);

        $this->assertDatabaseHas('network_commands', [
            'wifi_session_id' => $fixture['session']->id,
            'command_type' => 'authorize_client',
        ]);

        $this->assertDatabaseHas('network_commands', [
            'wifi_session_id' => $fixture['session']->id,
            'command_type' => 'disconnect_client',
        ]);

        Queue::assertPushed(ProcessNetworkCommand::class, 2);
    }

    /**
     * Test that an integration cannot control
     * a WiFi session belonging to another site.
     */
    public function test_it_rejects_integration_from_another_site(): void
    {
        Queue::fake();

        $fixture = $this->createFixture();

        $otherSite = Site::create([
            'organization_id' => $fixture['organization']->id,
            'created_by' => $fixture['user']->id,
            'name' => 'Temporary Test Site Two',
            'code' => 'TEST-SITE-002',
            'slug' => 'temporary-test-site-two',
            'status' => 'active',
        ]);

        $otherIntegration = NetworkIntegration::create([
            'organization_id' => $fixture['organization']->id,
            'site_id' => $otherSite->id,
            'provider' => 'omada',
            'name' => 'Temporary Omada Integration Two',
            'controller_url' => 'http://127.0.0.1:8043',
            'status' => 'pending',
        ]);

        try {
            app(NetworkCommandService::class)->authorizeClient(
                $otherIntegration,
                $fixture['session']
            );

            $this->fail('Expected InvalidArgumentException was not thrown.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame(
                'Network integration and WiFi session must belong to the same site.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseCount('network_commands', 0);

        Queue::assertNothingPushed();
    }

    /**
     * Test that a WiFi session without a client MAC
     * cannot create a network command.
     */
    public function test_it_rejects_session_without_client_mac(): void
    {
        Queue::fake();

        $fixture = $this->createFixture();

        $fixture['session']->update([
            'client_mac' => '',
        ]);

        try {
            app(NetworkCommandService::class)->authorizeClient(
                $fixture['integration'],
                $fixture['session']->fresh()
            );

            $this->fail('Expected InvalidArgumentException was not thrown.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame(
                'WiFi session does not have a client MAC address.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseCount('network_commands', 0);

        Queue::assertNothingPushed();
    }

    /**
     * Create temporary records required by the tests.
     *
     * @return array<string, mixed>
     */
    private function createFixture(): array
    {
        $user = User::factory()->create();

        $organization = Organization::create([
            'owner_user_id' => $user->id,
            'name' => 'Temporary Network Test Organization',
            'slug' => 'temporary-network-test-organization',
            'code' => 'TEST-ORG-001',
            'timezone' => 'Africa/Dar_es_Salaam',
            'status' => 'active',
        ]);

        $site = Site::create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'name' => 'Temporary Network Test Site',
            'code' => 'TEST-SITE-001',
            'slug' => 'temporary-network-test-site',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'site_id' => $site->id,
            'name' => 'Temporary Test Customer',
            'phone' => '255700000001',
            'status' => 'active',
        ]);

        $package = Package::create([
            'site_id' => $site->id,
            'name' => 'Temporary Test Package',
            'description' => 'Automated test package',
            'price' => 500,
            'currency' => 'TZS',
            'duration_minutes' => 60,
            'download_speed_kbps' => 5000,
            'upload_speed_kbps' => 2000,
            'device_limit' => 1,
            'is_active' => true,
        ]);

        $subscription = Subscription::create([
            'site_id' => $site->id,
            'customer_id' => $customer->id,
            'package_id' => $package->id,
            'reference' => 'TEST-SUB-' . uniqid(),
            'package_name' => $package->name,
            'amount' => 500,
            'duration_minutes' => 60,
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => now()->addHour(),
            'activated_at' => now(),
        ]);

        $session = WifiSession::create([
            'site_id' => $site->id,
            'subscription_id' => $subscription->id,
            'customer_id' => $customer->id,
            'client_mac' => 'AA:BB:CC:DD:EE:01',
            'client_ip' => '192.168.10.100',
            'started_at' => now(),
            'expires_at' => now()->addHour(),
            'status' => 'pending',
        ]);

        $integration = NetworkIntegration::create([
            'organization_id' => $organization->id,
            'site_id' => $site->id,
            'provider' => 'omada',
            'name' => 'Temporary Omada Integration',
            'controller_url' => 'http://127.0.0.1:8043',
            'status' => 'pending',
        ]);

        return compact(
            'user',
            'organization',
            'site',
            'customer',
            'package',
            'subscription',
            'session',
            'integration'
        );
    }
}
