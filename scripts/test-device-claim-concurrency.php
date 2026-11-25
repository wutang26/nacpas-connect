<?php

use App\Models\Customer;
use App\Models\Organization;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Site;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Network\DeviceClaimService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

function bootLaravel(): void
{
    $app = require __DIR__ . '/../bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
}

function assertTestDatabase(): void
{
    DB::purge();

    $database = DB::connection()->getDatabaseName();

    if ($database !== 'nacpas_concurrency_test') {
        throw new RuntimeException(
            "SAFETY STOP: Expected nacpas_concurrency_test, got {$database}"
        );
    }
}

/*
|--------------------------------------------------------------------------
| Worker process
|--------------------------------------------------------------------------
|
| Each worker is a separate PHP process with its own Laravel application
| and its own MySQL connection.
|
*/

if (($argv[1] ?? '') === '--worker') {
    $runDirectory = $argv[2] ?? '';
    $mac = $argv[3] ?? '';

    try {
        bootLaravel();
        assertTestDatabase();

        $token = file_get_contents($runDirectory . '/access-token');

        if ($token === false || $token === '') {
            throw new RuntimeException('Could not read test access token.');
        }

        // Tell the parent process that this worker is ready.
        file_put_contents($runDirectory . '/ready-' . $mac, 'ready');

        // Wait until all three workers are ready.
        $deadline = microtime(true) + 45;

        while (! file_exists($runDirectory . '/start')) {
            if (microtime(true) > $deadline) {
                throw new RuntimeException('Worker timed out waiting for start signal.');
            }

            usleep(10000);
        }

        try {
            $session = app(DeviceClaimService::class)->claim(
                $token,
                $mac,
                '192.168.50.100'
            );

            $result = [
                'mac' => $mac,
                'result' => 'accepted',
                'session_id' => $session->id,
            ];
        } catch (InvalidArgumentException $exception) {
            $result = [
                'mac' => $mac,
                'result' => 'rejected',
                'reason' => $exception->getMessage(),
            ];
        }

        file_put_contents(
            $runDirectory . '/result-' . $mac . '.json',
            json_encode($result, JSON_PRETTY_PRINT),
            LOCK_EX
        );

        DB::disconnect();

        exit(0);
    } catch (Throwable $exception) {
        file_put_contents(
            $runDirectory . '/error-' . ($mac ?: 'unknown') . '.txt',
            $exception::class . ': ' . $exception->getMessage(),
            LOCK_EX
        );

        fwrite(STDERR, $exception->getMessage() . PHP_EOL);

        exit(1);
    }
}

/*
|--------------------------------------------------------------------------
| Parent process
|--------------------------------------------------------------------------
*/

$runId = bin2hex(random_bytes(8));
$runDirectory = sys_get_temp_dir() . '/nacpasi-device-claim-' . $runId;

if (! mkdir($runDirectory, 0700, true)) {
    throw new RuntimeException('Could not create temporary test directory.');
}

$token = 'nacpasi-concurrency-test-' . bin2hex(random_bytes(24));

$macAddresses = [
    'AA:BB:CC:DD:EE:A1',
    'AA:BB:CC:DD:EE:A2',
    'AA:BB:CC:DD:EE:A3',
];

$processes = [];
$fixture = [];

try {
    bootLaravel();
    assertTestDatabase();

    echo PHP_EOL;
    echo "DATABASE: " . DB::connection()->getDatabaseName() . PHP_EOL;
    echo "Preparing isolated test fixture..." . PHP_EOL;

    file_put_contents(
        $runDirectory . '/access-token',
        $token,
        LOCK_EX
    );

    chmod($runDirectory . '/access-token', 0600);

    $user = User::factory()->create();

    $organization = Organization::create([
        'owner_user_id' => $user->id,
        'name' => 'Concurrency Test ' . $runId,
        'slug' => 'concurrency-test-' . $runId,
        'code' => 'CT-' . strtoupper(substr($runId, 0, 10)),
        'timezone' => 'Africa/Dar_es_Salaam',
        'status' => 'active',
    ]);

    $site = Site::create([
        'organization_id' => $organization->id,
        'created_by' => $user->id,
        'name' => 'Concurrency Test Site ' . $runId,
        'code' => 'CTS-' . strtoupper(substr($runId, 0, 10)),
        'slug' => 'concurrency-site-' . $runId,
        'status' => 'active',
    ]);

    $customer = Customer::create([
        'site_id' => $site->id,
        'name' => 'Concurrency Test Customer',
        'phone' => '255' . random_int(700000000, 799999999),
        'status' => 'active',
    ]);

    $package = Package::create([
        'site_id' => $site->id,
        'name' => 'Concurrency Test Package',
        'description' => 'Temporary concurrency test package',
        'price' => 500,
        'currency' => 'TZS',
        'duration_minutes' => 60,
        'download_speed_kbps' => 5000,
        'upload_speed_kbps' => 2000,
        'device_limit' => 2,
        'is_active' => true,
    ]);

    $expiresAt = now()->addHour();

    $subscription = Subscription::create([
        'site_id' => $site->id,
        'customer_id' => $customer->id,
        'package_id' => $package->id,
        'reference' => 'CT-SUB-' . strtoupper($runId),
        'package_name' => $package->name,
        'amount' => 500,
        'duration_minutes' => 60,
        'package_snapshot' => [
            'package_id' => $package->id,
            'name' => $package->name,
            'device_limit' => 2,
            'duration_minutes' => 60,
        ],
        'access_token_hash' => hash('sha256', $token),
        'status' => 'active',
        'starts_at' => now(),
        'expires_at' => $expiresAt,
        'activated_at' => now(),
    ]);

    $payment = Payment::create([
        'subscription_id' => $subscription->id,
        'customer_id' => $customer->id,
        'provider' => 'other',
        'provider_reference' => 'CT-PROVIDER-' . $runId,
        'internal_reference' => 'CT-PAY-' . $runId,
        'phone' => $customer->phone,
        'amount' => 500,
        'currency' => 'TZS',
        'status' => 'successful',
        'paid_at' => now(),
        'metadata' => [
            'test' => true,
        ],
    ]);

    $fixture = [
        'user_id' => $user->id,
        'organization_id' => $organization->id,
        'site_id' => $site->id,
        'customer_id' => $customer->id,
        'package_id' => $package->id,
        'subscription_id' => $subscription->id,
        'payment_id' => $payment->id,
    ];

    echo "Subscription ID: {$subscription->id}" . PHP_EOL;
    echo "Device limit: 2" . PHP_EOL;
    echo "Workers: 3" . PHP_EOL;

    /*
     * Start three independent PHP processes.
     */
    echo PHP_EOL . "Starting workers..." . PHP_EOL;

    foreach ($macAddresses as $mac) {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open(
            [
                PHP_BINARY,
                __FILE__,
                '--worker',
                $runDirectory,
                $mac,
            ],
            $descriptors,
            $pipes,
            dirname(__DIR__)
        );

        if (! is_resource($process)) {
            throw new RuntimeException("Could not start worker {$mac}.");
        }

        fclose($pipes[0]);

        $processes[$mac] = [
            'process' => $process,
            'stdout' => $pipes[1],
            'stderr' => $pipes[2],
        ];
    }

    /*
     * Wait until every worker has reached the barrier.
     */
    echo "Waiting for all workers to become ready..." . PHP_EOL;

    $deadline = microtime(true) + 45;

    while (true) {
        $readyCount = 0;

        foreach ($macAddresses as $mac) {
            if (file_exists($runDirectory . '/ready-' . $mac)) {
                $readyCount++;
            }
        }

        if ($readyCount === count($macAddresses)) {
            break;
        }

        if (microtime(true) > $deadline) {
            throw new RuntimeException(
                "Only {$readyCount} of 3 workers became ready."
            );
        }

        usleep(20000);
    }

    /*
     * Release all workers at approximately the same time.
     */
    echo "All workers ready. Releasing simultaneous claims..." . PHP_EOL;

    file_put_contents($runDirectory . '/start', 'GO', LOCK_EX);

    /*
     * Wait for all worker result files.
     */
    $deadline = microtime(true) + 60;

    while (true) {
        $resultCount = 0;

        foreach ($macAddresses as $mac) {
            if (file_exists($runDirectory . '/result-' . $mac . '.json')) {
                $resultCount++;
            }

            if (file_exists($runDirectory . '/error-' . $mac . '.txt')) {
                throw new RuntimeException(
                    file_get_contents($runDirectory . '/error-' . $mac . '.txt')
                );
            }
        }

        if ($resultCount === count($macAddresses)) {
            break;
        }

        if (microtime(true) > $deadline) {
            throw new RuntimeException(
                "Timed out waiting for worker results. Received {$resultCount}/3."
            );
        }

        usleep(20000);
    }

    /*
     * Collect and display results.
     */
    $results = [];

    foreach ($macAddresses as $mac) {
        $result = json_decode(
            file_get_contents($runDirectory . '/result-' . $mac . '.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $results[] = $result;

        echo PHP_EOL;
        echo "{$result['mac']}: " . strtoupper($result['result']);

        if (isset($result['session_id'])) {
            echo " (session ID: {$result['session_id']})";
        }

        if (isset($result['reason'])) {
            echo " - {$result['reason']}";
        }

        echo PHP_EOL;
    }

    /*
     * Verify persisted database results.
     */
    $accepted = count(array_filter(
        $results,
        fn (array $result) => $result['result'] === 'accepted'
    ));

    $rejected = count(array_filter(
        $results,
        fn (array $result) => $result['result'] === 'rejected'
    ));

    $sessionCount = DB::table('wifi_sessions')
        ->where('subscription_id', $subscription->id)
        ->whereNotNull('client_mac')
        ->count();

    echo PHP_EOL;
    echo "===== CONCURRENCY TEST SUMMARY =====" . PHP_EOL;
    echo "Workers started: 3" . PHP_EOL;
    echo "Accepted: {$accepted}" . PHP_EOL;
    echo "Rejected: {$rejected}" . PHP_EOL;
    echo "Persisted WiFi sessions: {$sessionCount}" . PHP_EOL;

    $passed = (
        $accepted === 2 &&
        $rejected === 1 &&
        $sessionCount === 2
    );

    if (! $passed) {
        throw new RuntimeException(
            'CONCURRENCY TEST FAILED: Expected 2 accepted, 1 rejected and 2 sessions.'
        );
    }

    echo PHP_EOL;
    echo "PASS: Device limit was respected under concurrent claims." . PHP_EOL;

} finally {
    /*
     * Release workers if an earlier step failed while they were waiting.
     */
    if (! file_exists($runDirectory . '/start')) {
        @file_put_contents($runDirectory . '/start', 'STOP', LOCK_EX);
    }

    /*
     * Close worker process handles.
     */
    foreach ($processes as $worker) {
        if (is_resource($worker['stdout'])) {
            fclose($worker['stdout']);
        }

        if (is_resource($worker['stderr'])) {
            fclose($worker['stderr']);
        }

        if (is_resource($worker['process'])) {
            proc_close($worker['process']);
        }
    }

    /*
     * Remove only this run's fixture records, in foreign-key order.
     */
    if (! empty($fixture)) {
        try {
            assertTestDatabase();

            DB::transaction(function () use ($fixture) {
                DB::table('wifi_sessions')
                    ->where('subscription_id', $fixture['subscription_id'])
                    ->delete();

                DB::table('payments')
                    ->where('id', $fixture['payment_id'])
                    ->delete();

                DB::table('subscriptions')
                    ->where('id', $fixture['subscription_id'])
                    ->delete();

                DB::table('packages')
                    ->where('id', $fixture['package_id'])
                    ->delete();

                DB::table('customers')
                    ->where('id', $fixture['customer_id'])
                    ->delete();

                DB::table('sites')
                    ->where('id', $fixture['site_id'])
                    ->delete();

                DB::table('organizations')
                    ->where('id', $fixture['organization_id'])
                    ->delete();

                DB::table('users')
                    ->where('id', $fixture['user_id'])
                    ->delete();
            });

            echo PHP_EOL . "Temporary fixture records cleaned up." . PHP_EOL;
        } catch (Throwable $cleanupException) {
            fwrite(
                STDERR,
                "CLEANUP WARNING: " . $cleanupException->getMessage() . PHP_EOL
            );
        }
    }

    /*
     * Remove only this run's temporary coordination files.
     */
    foreach (glob($runDirectory . '/*') ?: [] as $file) {
        @unlink($file);
    }

    @rmdir($runDirectory);
}
