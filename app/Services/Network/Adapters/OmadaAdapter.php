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
     * Test Omada connection using the reusable session manager.
     */
    public function testConnection(
        NetworkIntegration $integration
    ): array {
        try {
            $session = $this->sessionManager->getSession($integration);

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
                'message' => 'Successfully authenticated with Omada Cloud Essentials.',
                'controller_version' => $integration->controller_version,
                'csrf_token_received' => true,
                'session_cookie_received' => true,
            ];
        } catch (Throwable $exception) {
            report($exception);

            $integration->forceFill([
                'status' => 'error',
                'last_error' => 'Omada authentication failed. Check the server logs.',
            ])->save();

            return [
                'success' => false,
                'provider' => 'omada',
                'message' => 'Unable to authenticate with Omada. Check the integration configuration and server logs.',
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
