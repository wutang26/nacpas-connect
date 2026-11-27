<?php

namespace App\Services\Network;

use App\Jobs\ProcessNetworkCommand;
use App\Models\NetworkCommand;
use App\Models\NetworkIntegration;
use App\Models\WifiSession;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class NetworkCommandService
{
    public function authorizeClient(
        NetworkIntegration $integration,
        WifiSession $session
    ): NetworkCommand {
        return $this->createCommand(
            $integration,
            $session,
            'authorize_client'
        );
    }

    public function disconnectClient(
        NetworkIntegration $integration,
        WifiSession $session
    ): NetworkCommand {
        return $this->createCommand(
            $integration,
            $session,
            'disconnect_client'
        );
    }

    private function createCommand(
        NetworkIntegration $integration,
        WifiSession $session,
        string $commandType
    ): NetworkCommand {
        if ((int) $integration->site_id !== (int) $session->site_id) {
            throw new InvalidArgumentException(
                'Network integration and WiFi session must belong to the same site.'
            );
        }

        if (empty($session->client_mac)) {
            throw new InvalidArgumentException(
                'WiFi session does not have a client MAC address.'
            );
        }

        try {
            [$command, $shouldDispatch] = DB::transaction(function () use (
                $integration,
                $session,
                $commandType
            ) {
                $existing = NetworkCommand::query()
                    ->where('wifi_session_id', $session->id)
                    ->where('command_type', $commandType)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    /*
                     * Unknown means the controller may already have
                     * processed the request. Never automatically resend it.
                     *
                     * Failed commands may be retried because the processor
                     * only records uncertain external outcomes as unknown.
                     */
                    if (in_array($existing->status, [
                        'pending',
                        'processing',
                        'succeeded',
                        'unknown',
                    ], true)) {
                        return [$existing, false];
                    }

                    if ($existing->status !== 'failed') {
                        return [$existing, false];
                    }

                    $existing->update([
                        'network_integration_id' => $integration->id,
                        'site_id' => $session->site_id,
                        'device_id' => $session->device_id,
                        'target_mac' => strtoupper($session->client_mac),
                        'payload' => [
                            'client_mac' => strtoupper($session->client_mac),
                            'client_ip' => $session->client_ip,
                            'wifi_session_id' => $session->id,
                            'expires_at' => $session->expires_at?->toISOString(),
                        ],
                        'response' => null,
                        'status' => 'pending',
                        'attempts' => 0,
                        'last_attempt_at' => null,
                        'executed_at' => null,
                        'error_message' => null,
                    ]);

                    return [$existing->fresh(), true];
                }

                $command = NetworkCommand::create([
                    'network_integration_id' => $integration->id,
                    'site_id' => $session->site_id,
                    'device_id' => $session->device_id,
                    'wifi_session_id' => $session->id,
                    'command_uuid' => (string) Str::uuid(),
                    'command_type' => $commandType,
                    'target_mac' => strtoupper($session->client_mac),
                    'payload' => [
                        'client_mac' => strtoupper($session->client_mac),
                        'client_ip' => $session->client_ip,
                        'wifi_session_id' => $session->id,
                        'expires_at' => $session->expires_at?->toISOString(),
                    ],
                    'status' => 'pending',
                    'attempts' => 0,
                ]);

                return [$command, true];
            });
        } catch (QueryException $exception) {
            // MySQL duplicate key error.
            $isDuplicateKey = (int) ($exception->errorInfo[1] ?? 0) === 1062;

            if (!$isDuplicateKey) {
                throw $exception;
            }

            // Another request may have created the command first.
            $command = NetworkCommand::query()
                ->where('wifi_session_id', $session->id)
                ->where('command_type', $commandType)
                ->first();

            if (!$command) {
                throw $exception;
            }

            $shouldDispatch = false;
        }

        if ($shouldDispatch) {
            ProcessNetworkCommand::dispatch($command->id)
                ->afterCommit();
        }

        return $command;
    }
}
