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

            /*
             * Commands with final or uncertain outcomes
             * must never be executed automatically again.
             */
            if (in_array($command->status, [
                'succeeded',
                'failed',
                'unknown',
            ], true)) {
                return;
            }

            /*
             * Validate prerequisites before contacting Omada.
             */
            if (!$command->integration || !$command->wifiSession) {
                NetworkCommand::whereKey($commandId)
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'failed',
                        'error_message' => 'Network integration or WiFi session could not be found.',
                        'executed_at' => now(),
                    ]);

                return;
            }

            if (!in_array($command->command_type, [
                'authorize_client',
                'disconnect_client',
            ], true)) {
                NetworkCommand::whereKey($commandId)
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'failed',
                        'error_message' => 'Unsupported network command type.',
                        'executed_at' => now(),
                    ]);

                return;
            }

            /*
             * Claim the command exactly once.
             */
            $claimed = DB::transaction(function () use ($commandId) {
                $lockedCommand = NetworkCommand::query()
                    ->lockForUpdate()
                    ->findOrFail($commandId);

                if (in_array($lockedCommand->status, [
                    'succeeded',
                    'failed',
                    'unknown',
                ], true)) {
                    return false;
                }

                /*
                 * A previous worker already started this command.
                 * We cannot know whether the controller executed it.
                 */
                if ($lockedCommand->status === 'processing') {
                    $lockedCommand->update([
                        'status' => 'unknown',
                        'error_message' =>
                            'Previous execution was interrupted. Controller outcome could not be confirmed. Manual reconciliation is required.',
                        'executed_at' => now(),
                    ]);

                    return false;
                }

                if ($lockedCommand->status !== 'pending') {
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

            /*
             * Contact the network controller.
             */
            try {
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
                };
            } catch (Throwable $exception) {
                report($exception);

                /*
                 * The request may already have reached the controller.
                 * Never automatically retry an ambiguous outcome.
                 */
                NetworkCommand::whereKey($commandId)
                    ->where('status', 'processing')
                    ->update([
                        'status' => 'unknown',
                        'response' => [
                            'success' => false,
                            'outcome' => 'unknown',
                            'message' => 'Controller response could not be confirmed.',
                        ],
                        'error_message' =>
                            'Controller request outcome is unknown. Manual reconciliation is required.',
                        'executed_at' => now(),
                    ]);

                return;
            }

            /*
             * A successful controller response is confirmed.
             */
            if (($response['success'] ?? false) === true) {
                NetworkCommand::whereKey($commandId)
                    ->where('status', 'processing')
                    ->update([
                        'status' => 'succeeded',
                        'response' => $response,
                        'executed_at' => now(),
                        'error_message' => null,
                    ]);

                return;
            }

            /*
             * An explicit rejection is a known failure.
             * An ambiguous response must become unknown.
             */
            $explicitRejection =
                ($response['outcome'] ?? null) === 'rejected';

            NetworkCommand::whereKey($commandId)
                ->where('status', 'processing')
                ->update([
                    'status' => $explicitRejection
                        ? 'failed'
                        : 'unknown',

                    'response' => $response,

                    'executed_at' => now(),

                    'error_message' => $explicitRejection
                        ? ($response['message'] ?? 'Network command was rejected.')
                        : 'Controller outcome could not be confirmed. Manual reconciliation is required.',
                ]);
        } finally {
            $lock->release();
        }
    }
}