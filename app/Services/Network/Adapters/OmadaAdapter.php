<?php

namespace App\Services\Network\Adapters;

use App\Models\NetworkIntegration;
use App\Models\WifiSession;
use App\Services\Network\Contracts\NetworkAdapterInterface;
use App\Services\Network\Omada\OmadaSessionManager;
use Throwable;

class OmadaAdapter implements NetworkAdapterInterface
{
    public function __construct(
        private OmadaSessionManager $sessionManager
    ) {
    }

    /**
     * Test Omada using a fresh API authentication request.
     *
     * Session credentials are only used internally and are never returned.
     */
    public function testConnection(
        NetworkIntegration $integration
    ): array {
        try {
            // Force a new login instead of accepting an old cached session.
            $session = $this->sessionManager->refresh($integration);

            if (
                empty($session['csrf_token']) ||
                empty($session['session_cookie'])
            ) {
                throw new \RuntimeException(
                    'Omada session data is incomplete.'
                );
            }

            $integration->forceFill([
                'status' => 'active',
                'last_connected_at' => now(),
                'last_error' => null,
            ])->save();

            return [
                'success' => true,
                'provider' => 'omada',
                'message' => 'Fresh authentication with the Omada API succeeded.',
                'controller_version' => $integration->controller_version,
            ];
        } catch (Throwable $exception) {
            report($exception);

            $integration->forceFill([
                'status' => 'error',
                'last_error' => 'Omada authentication failed. Check the integration configuration and server logs.',
            ])->save();

            return [
                'success' => false,
                'provider' => 'omada',
                'message' => 'Omada API authentication failed. Check the integration configuration and server logs.',
                'controller_version' => $integration->controller_version,
            ];
        }
    }

    public function authorizeClient(
        NetworkIntegration $integration,
        WifiSession $session
    ): array {
        return [
            'success' => false,
            'provider' => 'omada',
            'message' => 'Client authorization is not implemented yet.',
        ];
    }

    public function disconnectClient(
        NetworkIntegration $integration,
        WifiSession $session
    ): array {
        return [
            'success' => false,
            'provider' => 'omada',
            'message' => 'Client disconnection is not implemented yet.',
        ];
    }

    public function getClientStatus(
        NetworkIntegration $integration,
        string $macAddress
    ): array {
        return [
            'success' => false,
            'provider' => 'omada',
            'mac_address' => $macAddress,
            'message' => 'Client status lookup is not implemented yet.',
        ];
    }
}
