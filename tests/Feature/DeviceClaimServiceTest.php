<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Site;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WifiSession;
use App\Services\Network\DeviceClaimService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use InvalidArgumentException;
use Tests\TestCase;

class DeviceClaimServiceTest extends TestCase
{
    use DatabaseMigrations;

    private const ACCESS_TOKEN = 'test-subscription-access-token-123456789';

    public function test_it_registers_a_device_for_a_paid_active_subscription(): void
    {
        $fixture = $this->createFixture();

        $session = app(DeviceClaimService::class)->claim(
            self::ACCESS_TOKEN,
            'aa:bb:cc:dd:ee:01',
            '192.168.10.100'
        );

        $this->assertDatabaseCount('wifi_sessions', 1);

        $this->assertSame($fixture['subscription']->id, $session->subscription_id);
        $this->assertSame($fixture['site']->id, $session->site_id);
        $this->assertSame($fixture['customer']->id, $session->customer_id);
        $this->assertSame('AA:BB:CC:DD:EE:01', $session->client_mac);
        $this->assertSame('192.168.10.100', $session->client_ip);
        $this->assertSame('pending', $session->status);
        $this->assertNull($session->started_at);

        $this->assertEquals(
            $fixture['subscription']->expires_at->toDateTimeString(),
            $session->expires_at->toDateTimeString()
        );
    }

    public function test_same_mac_returns_existing_session_without_creating_duplicate(): void
    {
        $fixture = $this->createFixture();

        $service = app(DeviceClaimService::class);

        $first = $service->claim(
            self::ACCESS_TOKEN,
            'AA:BB:CC:DD:EE:02',
            '192.168.10.101'
        );

        $second = $service->claim(
            self::ACCESS_TOKEN,
            'aa-bb-cc-dd-ee-02',
            '192.168.10.102'
        );

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('wifi_sessions', 1);
    }

    public function test_it_rejects_an_invalid_access_token(): void
    {
        $this->createFixture();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Invalid subscription access token.'
        );

        app(DeviceClaimService::class)->claim(
            'incorrect-token',
            'AA:BB:CC:DD:EE:03'
        );
    }

    public function test_it_rejects_a_pending_subscription(): void
    {
        $fixture = $this->createFixture();

        $fixture['subscription']->update([
            'status' => 'pending',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The subscription is not active.'
        );

        app(DeviceClaimService::class)->claim(
            self::ACCESS_TOKEN,
            'AA:BB:CC:DD:EE:04'
        );
    }

    public function test_it_rejects_a_subscription_without_confirmed_payment(): void
    {
        $fixture = $this->createFixture();

        $fixture['payment']->update([
            'status' => 'pending',
            'paid_at' => null,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The subscription does not have a confirmed successful payment.'
        );

        app(DeviceClaimService::class)->claim(
            self::ACCESS_TOKEN,
            'AA:BB:CC:DD:EE:05'
        );
    }

    public function test_it_rejects_an_expired_subscription(): void
    {
        $fixture = $this->createFixture();

        $fixture['subscription']->update([
            'expires_at' => now()->subMinute(),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The subscription has expired.'
        );

        app(DeviceClaimService::class)->claim(
            self::ACCESS_TOKEN,
            'AA:BB:CC:DD:EE:06'
        );
    }

    public function test_it_enforces_the_device_limit_from_purchase_snapshot(): void
    {
        $fixture = $this->createFixture(deviceLimit: 1);

        WifiSession::create([
            'site_id' => $fixture['site']->id,
            'subscription_id' => $fixture['subscription']->id,
            'customer_id' => $fixture['customer']->id,
            'client_mac' => 'AA:BB:CC:DD:EE:07',
            'client_ip' => '192.168.10.107',
            'expires_at' => $fixture['subscription']->expires_at,
            'status' => 'disconnected',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The subscription has reached its device limit.'
        );

        app(DeviceClaimService::class)->claim(
            self::ACCESS_TOKEN,
            'AA:BB:CC:DD:EE:08'
        );
    }

    public function test_it_allows_multiple_devices_up_to_the_snapshot_limit(): void
    {
        $fixture = $this->createFixture(deviceLimit: 2);

        $service = app(DeviceClaimService::class);

        $first = $service->claim(
            self::ACCESS_TOKEN,
            'AA:BB:CC:DD:EE:09'
        );

        $second = $service->claim(
            self::ACCESS_TOKEN,
            'AA:BB:CC:DD:EE:10'
        );

        $this->assertNotSame($first->id, $second->id);
        $this->assertDatabaseCount('wifi_sessions', 2);
    }

    public function test_it_rejects_invalid_mac_addresses(): void
    {
        $this->createFixture();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'A valid client MAC address is required.'
        );

        app(DeviceClaimService::class)->claim(
            self::ACCESS_TOKEN,
            'NOT-A-MAC'
        );
    }

    public function test_it_rejects_invalid_ip_addresses(): void
    {
        $this->createFixture();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The client IP address is invalid.'
        );

        app(DeviceClaimService::class)->claim(
            self::ACCESS_TOKEN,
            'AA:BB:CC:DD:EE:11',
            '999.999.999.999'
        );
    }

    private function createFixture(int $deviceLimit = 1): array
    {
        $user = User::factory()->create();

        $organization = Organization::create([
            'owner_user_id' => $user->id,
            'name' => 'Device Claim Test Organization',
            'slug' => 'device-claim-test-organization',
            'code' => 'CLAIM-ORG-001',
            'timezone' => 'Africa/Dar_es_Salaam',
            'status' => 'active',
        ]);

        $site = Site::create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'name' => 'Device Claim Test Site',
            'code' => 'CLAIM-SITE-001',
            'slug' => 'device-claim-test-site',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'site_id' => $site->id,
            'name' => 'Device Claim Test Customer',
            'phone' => '255700000099',
            'status' => 'active',
        ]);

        $package = Package::create([
            'site_id' => $site->id,
            'name' => 'Device Claim Test Package',
            'description' => 'Automated test package',
            'price' => 500,
            'currency' => 'TZS',
            'duration_minutes' => 60,
            'download_speed_kbps' => 5000,
            'upload_speed_kbps' => 2000,
            'device_limit' => $deviceLimit,
            'is_active' => true,
        ]);

        $expiresAt = now()->addHour();

        $subscription = Subscription::create([
            'site_id' => $site->id,
            'customer_id' => $customer->id,
            'package_id' => $package->id,
            'reference' => 'CLAIM-SUB-' . uniqid(),
            'package_name' => $package->name,
            'amount' => 500,
            'duration_minutes' => 60,
            'package_snapshot' => [
                'package_id' => $package->id,
                'name' => $package->name,
                'device_limit' => $deviceLimit,
                'duration_minutes' => 60,
            ],
            'access_token_hash' => hash(
                'sha256',
                self::ACCESS_TOKEN
            ),
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => $expiresAt,
            'activated_at' => now(),
        ]);

        $payment = Payment::create([
            'subscription_id' => $subscription->id,
            'customer_id' => $customer->id,
            'provider' => 'other',
            'provider_reference' => 'CLAIM-PROVIDER-' . uniqid(),
            'internal_reference' => 'CLAIM-PAY-' . uniqid(),
            'phone' => $customer->phone,
            'amount' => 500,
            'currency' => 'TZS',
            'status' => 'successful',
            'paid_at' => now(),
            'metadata' => [
                'verified_by_fastlipa' => true,
            ],
        ]);

        return compact(
            'user',
            'organization',
            'site',
            'customer',
            'package',
            'subscription',
            'payment'
        );
    }
}
