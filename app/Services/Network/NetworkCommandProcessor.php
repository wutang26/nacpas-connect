<?php

namespace App\Services\Network;

use App\Jobs\ProcessNetworkCommand;
use App\Models\NetworkCommand;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class NetworkCommandProcessor
{
    public function process(
        int $commandId,
        ProcessNetworkCommand $job
    ): void {
        $lock = Cache::lock("network-command:{$commandId}", 120);

        if (!$lock->get()) {
            $job->release(10);
            return;
        }

        try {
            $command = NetworkCommand::with([
                'integration',
                'wifiSession',
            ])->findOrFail($commandId);

            if (in_array($command->status, [
                'succeeded',
                'failed',
            ], true)) {
                return;
            }

            if (!$command->integration || !$command->wifiSession) {
                throw new RuntimeException(
                    'Network integration or WiFi session could not be found.'
                );
            }

            $claimed = DB::transaction(function () use ($commandId) {
                $lockedCommand = NetworkCommand::query()
                    ->lockForUpdate()
                    ->findOrFail($commandId);

                if (in_array($lockedCommand->status, [
                    'succeeded',
                    'failed',
                ], true)) {
                    return false;
                }

                if (!in_array($lockedCommand->status, [
                    'pending',
                    'processing',
                ], true)) {
                    return false;
                }

                $lockedCommand->update([
                    'status' => 'processing',
                    'attempts' => $lockedCommand->attempts + 1,
                    'last_attempt_at' => now(),
                    'error_message' => null,
                ]);

                return true;
            });

            if (!$claimed) {
                return;
            }

            $manager = app(NetworkManager::class);

            $response = match ($command->command_type) {
                'authorize_client' => $manager->authorizeClient(
                    $command->integration,
                    $command->wifiSession
                ),

                'disconnect_client' => $manager->disconnectClient(
                    $command->integration,
                    $command->wifiSession
                ),

                default => throw new RuntimeException(
                    "Unsupported command type: {$command->command_type}"
                ),
            };

            $success = ($response['success'] ?? false) === true;

            NetworkCommand::whereKey($commandId)
                ->where('status', 'processing')
                ->update([
                    'status' => $success ? 'succeeded' : 'failed',
                    'response' => $response,
                    'executed_at' => now(),
                    'error_message' => $success
                        ? null
                        : ($response['message'] ?? 'Network command failed.'),
                ]);
        } catch (Throwable $exception) {
            NetworkCommand::whereKey($commandId)
                ->where('status', 'processing')
                ->update([
                    'error_message' => $exception->getMessage(),
                ]);

            throw $exception;
        } finally {
            $lock->release();
        }
    }
}
