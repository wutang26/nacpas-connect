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
use App\Services\Network\NetworkCommandProcessor;
use App\Services\Network\NetworkManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class NetworkCommandProcessorTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorize_command_is_marked_succeeded_when_adapter_succeeds(): void
    {
        $fixture = $this->createFixture();
        $command = $this->createCommand($fixture, 'authorize_client');

        $this->mock(NetworkManager::class, function (MockInterface $mock) use ($fixture) {
            $mock->shouldReceive('authorizeClient')
                ->once()
                ->withArgs(fn ($integration, $session) =>
                    $integration->id === $fixture['integration']->id
                    && $session->id === $fixture['session']->id
                )
                ->andReturn([
                    'success' => true,
                    'provider' => 'omada',
                    'message' => 'Client authorized successfully.',
                ]);
        });

        app(NetworkCommandProcessor::class)->process(
            $command->id,
            new ProcessNetworkCommand($command->id)
        );

        $command->refresh();

        $this->assertSame('succeeded', $command->status);
        $this->assertSame(1, $command->attempts);
        $this->assertNotNull($command->last_attempt_at);
        $this->assertNotNull($command->executed_at);
        $this->assertNull($command->error_message);
        $this->assertSame(
            'Client authorized successfully.',
            $command->response['message']
        );
    }

    public function test_explicit_controller_rejection_marks_command_failed(): void
    {
        $fixture = $this->createFixture();
        $command = $this->createCommand($fixture, 'authorize_client');

        $this->mock(NetworkManager::class, function (MockInterface $mock) use ($fixture) {
            $mock->shouldReceive('authorizeClient')
                ->once()
                ->withArgs(fn ($integration, $session) =>
                    $integration->id === $fixture['integration']->id
                    && $session->id === $fixture['session']->id
                )
                ->andReturn([
                    'success' => false,
                    'outcome' => 'rejected',
                    'provider' => 'omada',
                    'message' => 'Omada authorization failed.',
                ]);
        });

        app(NetworkCommandProcessor::class)->process(
            $command->id,
            new ProcessNetworkCommand($command->id)
        );

        $command->refresh();

        $this->assertSame('failed', $command->status);
        $this->assertSame(1, $command->attempts);
        $this->assertNotNull($command->last_attempt_at);
        $this->assertNotNull($command->executed_at);
        $this->assertSame(
            'Omada authorization failed.',
            $command->error_message
        );
        $this->assertFalse($command->response['success']);
        $this->assertSame('rejected', $command->response['outcome']);
    }

    public function test_uncertain_controller_response_marks_command_unknown(): void
    {
        $fixture = $this->createFixture();
        $command = $this->createCommand($fixture, 'authorize_client');

        $this->mock(NetworkManager::class, function (MockInterface $mock) use ($fixture) {
            $mock->shouldReceive('authorizeClient')
                ->once()
                ->withArgs(fn ($integration, $session) =>
                    $integration->id === $fixture['integration']->id
                    && $session->id === $fixture['session']->id
                )
                ->andReturn([
                    'success' => false,
                    'provider' => 'omada',
                    'message' => 'Controller response unavailable.',
                ]);
        });

        app(NetworkCommandProcessor::class)->process(
            $command->id,
            new ProcessNetworkCommand($command->id)
        );

        $command->refresh();

        $this->assertSame('unknown', $command->status);
        $this->assertSame(1, $command->attempts);
        $this->assertNotNull($command->executed_at);
        $this->assertSame(
            'Controller outcome could not be confirmed. Manual reconciliation is required.',
            $command->error_message
        );
        $this->assertSame('unknown', $command->response['outcome']);
    }

    public function test_succeeded_command_is_not_executed_again(): void
    {
        $fixture = $this->createFixture();
        $command = $this->createCommand($fixture, 'authorize_client');

        $command->update([
            'status' => 'succeeded',
            'attempts' => 1,
        ]);

        $this->mock(NetworkManager::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('authorizeClient');
            $mock->shouldNotReceive('disconnectClient');
        });

        app(NetworkCommandProcessor::class)->process(
            $command->id,
            new ProcessNetworkCommand($command->id)
        );

        $command->refresh();

        $this->assertSame('succeeded', $command->status);
        $this->assertSame(1, $command->attempts);
    }

    public function test_failed_command_is_not_executed_again(): void
    {
        $fixture = $this->createFixture();
        $command = $this->createCommand($fixture, 'authorize_client');

        $command->update([
            'status' => 'failed',
            'attempts' => 2,
            'error_message' => 'Controller rejected the command.',
        ]);

        $this->mock(NetworkManager::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('authorizeClient');
            $mock->shouldNotReceive('disconnectClient');
        });

        app(NetworkCommandProcessor::class)->process(
            $command->id,
            new ProcessNetworkCommand($command->id)
        );

        $command->refresh();

        $this->assertSame('failed', $command->status);
        $this->assertSame(2, $command->attempts);
        $this->assertSame(
            'Controller rejected the command.',
            $command->error_message
        );
    }

    public function test_unknown_command_is_not_executed_again(): void
    {
        $fixture = $this->createFixture();
        $command = $this->createCommand($fixture, 'authorize_client');

        $command->update([
            'status' => 'unknown',
            'attempts' => 2,
            'error_message' => 'Manual reconciliation is required.',
        ]);

        $this->mock(NetworkManager::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('authorizeClient');
            $mock->shouldNotReceive('disconnectClient');
        });

        app(NetworkCommandProcessor::class)->process(
            $command->id,
            new ProcessNetworkCommand($command->id)
        );

        $command->refresh();

        $this->assertSame('unknown', $command->status);
        $this->assertSame(2, $command->attempts);
        $this->assertSame(
            'Manual reconciliation is required.',
            $command->error_message
        );
    }

    public function test_disconnect_command_uses_disconnect_client(): void
    {
        $fixture = $this->createFixture();
        $command = $this->createCommand($fixture, 'disconnect_client');

        $this->mock(NetworkManager::class, function (MockInterface $mock) use ($fixture) {
            $mock->shouldReceive('disconnectClient')
                ->once()
                ->withArgs(fn ($integration, $session) =>
                    $integration->id === $fixture['integration']->id
                    && $session->id === $fixture['session']->id
                )
                ->andReturn([
                    'success' => true,
                    'provider' => 'omada',
                    'message' => 'Client disconnected successfully.',
                ]);

            $mock->shouldNotReceive('authorizeClient');
        });

        app(NetworkCommandProcessor::class)->process(
            $command->id,
            new ProcessNetworkCommand($command->id)
        );

        $command->refresh();

        $this->assertSame('succeeded', $command->status);
        $this->assertSame(1, $command->attempts);
        $this->assertSame(
            'Client disconnected successfully.',
            $command->response['message']
        );
    }

    public function test_exception_marks_command_unknown_without_automatic_retry(): void
    {
        $fixture = $this->createFixture();
        $command = $this->createCommand($fixture, 'authorize_client');

        $this->mock(NetworkManager::class, function (MockInterface $mock) use ($fixture) {
            $mock->shouldReceive('authorizeClient')
                ->once()
                ->withArgs(fn ($integration, $session) =>
                    $integration->id === $fixture['integration']->id
                    && $session->id === $fixture['session']->id
                )
                ->andThrow(new RuntimeException('Temporary controller error.'));
        });

        app(NetworkCommandProcessor::class)->process(
            $command->id,
            new ProcessNetworkCommand($command->id)
        );

        $command->refresh();

        $this->assertSame('unknown', $command->status);
        $this->assertSame(1, $command->attempts);
        $this->assertNotNull($command->executed_at);

        $this->assertSame(
            'Controller request outcome is unknown. Manual reconciliation is required.',
            $command->error_message
        );

        $this->assertSame('unknown', $command->response['outcome']);
    }

    public function test_failed_job_marks_processing_command_as_unknown(): void
    {
        $fixture = $this->createFixture();
        $command = $this->createCommand($fixture, 'authorize_client');

        $command->update([
            'status' => 'processing',
            'attempts' => 3,
        ]);

        $job = new ProcessNetworkCommand($command->id);

        $job->failed(new RuntimeException('Maximum retries exceeded.'));

        $command->refresh();

        $this->assertSame('unknown', $command->status);
        $this->assertSame(3, $command->attempts);

        $this->assertStringContainsString(
            'Queue worker failed after execution started.',
            $command->error_message
        );

        $this->assertStringContainsString(
            'Maximum retries exceeded.',
            $command->error_message
        );

        $this->assertSame('unknown', $command->response['outcome']);
        $this->assertNotNull($command->executed_at);
    }

    public function test_failed_job_marks_pending_command_as_failed(): void
    {
        $fixture = $this->createFixture();
        $command = $this->createCommand($fixture, 'authorize_client');

        $job = new ProcessNetworkCommand($command->id);

        $job->failed(new RuntimeException('Queue dispatch failed.'));

        $command->refresh();

        $this->assertSame('failed', $command->status);
        $this->assertSame(0, $command->attempts);

        $this->assertStringContainsString(
            'Queue job failed before network command execution:',
            $command->error_message
        );

        $this->assertStringContainsString(
            'Queue dispatch failed.',
            $command->error_message
        );
    }

    public function test_failed_job_does_not_change_succeeded_command(): void
    {
        $fixture = $this->createFixture();
        $command = $this->createCommand($fixture, 'authorize_client');

        $executedAt = now()->subMinute();

        $command->update([
            'status' => 'succeeded',
            'attempts' => 1,
            'executed_at' => $executedAt,
            'error_message' => null,
        ]);

        $job = new ProcessNetworkCommand($command->id);

        $job->failed(new RuntimeException('Late queue failure.'));

        $command->refresh();

        $this->assertSame('succeeded', $command->status);
        $this->assertSame(1, $command->attempts);
        $this->assertNull($command->error_message);

        $this->assertEquals(
            $executedAt->toDateTimeString(),
            $command->executed_at->toDateTimeString()
        );
    }

    private function createCommand(
        array $fixture,
        string $commandType
    ): NetworkCommand {
        return NetworkCommand::create([
            'network_integration_id' => $fixture['integration']->id,
            'site_id' => $fixture['site']->id,
            'wifi_session_id' => $fixture['session']->id,
            'command_uuid' => (string) Str::uuid(),
            'command_type' => $commandType,
            'target_mac' => $fixture['session']->client_mac,
            'payload' => [
                'client_mac' => $fixture['session']->client_mac,
                'wifi_session_id' => $fixture['session']->id,
            ],
            'status' => 'pending',
            'attempts' => 0,
        ]);
    }

    private function createFixture(): array
    {
        $user = User::factory()->create();

        $organization = Organization::create([
            'owner_user_id' => $user->id,
            'name' => 'Processor Test Organization',
            'slug' => 'processor-test-organization',
            'code' => 'PROC-ORG-001',
            'timezone' => 'Africa/Dar_es_Salaam',
            'status' => 'active',
        ]);

        $site = Site::create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'name' => 'Processor Test Site',
            'code' => 'PROC-SITE-001',
            'slug' => 'processor-test-site',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'site_id' => $site->id,
            'name' => 'Processor Test Customer',
            'phone' => '255700000002',
            'status' => 'active',
        ]);

        $package = Package::create([
            'site_id' => $site->id,
            'name' => 'Processor Test Package',
            'description' => 'Automated processor test',
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
            'reference' => 'PROC-SUB-' . uniqid(),
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
            'client_mac' => 'AA:BB:CC:DD:EE:02',
            'client_ip' => '192.168.10.101',
            'started_at' => now(),
            'expires_at' => now()->addHour(),
            'status' => 'pending',
        ]);

        $integration = NetworkIntegration::create([
            'organization_id' => $organization->id,
            'site_id' => $site->id,
            'provider' => 'omada',
            'name' => 'Processor Test Integration',
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
