<?php

namespace App\Services\Network;

use App\Jobs\ProcessNetworkCommand;
use App\Models\NetworkCommand;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

            // Terminal states must never be executed again.
            if (in_array($command->status, [
                'succeeded',
                'failed',
                'unknown',
            ], true)) {
                return;
            }

            // Validate local prerequisites before contacting the controller.
            if (!$command->integration || !$command->wifiSession) {
                NetworkCommand::query()
                    ->whereKey($commandId)
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'failed',
                        'error_message' =>
                            'Network integration or WiFi session could not be found.',
                        'executed_at' => now(),
                    ]);

                return;
            }

            if (!in_array($command->command_type, [
                'authorize_client',
                'disconnect_client',
            ], true)) {
                NetworkCommand::query()
                    ->whereKey($commandId)
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'failed',
                        'error_message' =>
                            'Unsupported network command type.',
                        'executed_at' => now(),
                    ]);

                return;
            }

            /*
             * Claim only pending commands.
             *
             * A command already in processing means a previous execution
             * may have reached the controller. Never send it again.
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

                if ($lockedCommand->status === 'processing') {
                    $lockedCommand->update([
                        'status' => 'unknown',
                        'response' => [
                            'success' => false,
                            'outcome' => 'unknown',
                            'message' =>
                                'Previous execution was interrupted. Controller outcome could not be confirmed.',
                        ],
                        'error_message' =>
                            'Previous execution was interrupted. Manual reconciliation is required.',
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
             * From this point, the external request may reach Omada.
             * Any exception therefore has an uncertain outcome.
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

                NetworkCommand::query()
                    ->whereKey($commandId)
                    ->where('status', 'processing')
                    ->update([
                        'status' => 'unknown',
                        'response' => [
                            'success' => false,
                            'outcome' => 'unknown',
                            'message' =>
                                'Controller response could not be confirmed.',
                        ],
                        'error_message' =>
                            'Controller request outcome is unknown. Manual reconciliation is required.',
                        'executed_at' => now(),
                    ]);

                // Do not throw: automatic queue retry could duplicate the request.
                return;
            }

            $success = ($response['success'] ?? false) === true;

            $explicitRejection =
                ($response['outcome'] ?? null) === 'rejected';

            if ($success) {
                $status = 'succeeded';
                $errorMessage = null;
            } elseif ($explicitRejection) {
                $status = 'failed';
                $errorMessage = $response['message']
                    ?? 'Network command was rejected by the controller.';
            } else {
                $status = 'unknown';
                $errorMessage =
                    'Controller outcome could not be confirmed. Manual reconciliation is required.';

                $response['outcome'] = 'unknown';
            }

            NetworkCommand::query()
                ->whereKey($commandId)
                ->where('status', 'processing')
                ->update([
                    'status' => $status,
                    'response' => $response,
                    'executed_at' => now(),
                    'error_message' => $errorMessage,
                ]);
        } finally {
            $lock->release();
        }
    }
}
