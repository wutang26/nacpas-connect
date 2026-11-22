<?php

namespace App\Services\Network\Adapters;

use App\Models\NetworkIntegration;
use App\Models\WifiSession;
use App\Services\Network\Contracts\NetworkAdapterInterface;

class OmadaAdapter implements NetworkAdapterInterface
{
    public function testConnection(
        NetworkIntegration $integration
    ): array {
        // Actual Omada authentication will be implemented
        // after confirming the Controller API version.

        return [
            'success' => false,
            'provider' => 'omada',
            'message' => 'Omada API authentication is not configured yet.',
        ];
    }

    public function authorizeClient(
        NetworkIntegration $integration,
        WifiSession $session
    ): array {
        // Actual External Portal authorization will be implemented
        // using the verified Omada Controller API.

        return [
            'success' => false,
            'provider' => 'omada',
            'message' => 'Omada client authorization is not implemented yet.',
        ];
    }

    public function disconnectClient(
        NetworkIntegration $integration,
        WifiSession $session
    ): array {
        // Actual client disconnection will be implemented
        // using the verified Omada Controller API.

        return [
            'success' => false,
            'provider' => 'omada',
            'message' => 'Omada client disconnection is not implemented yet.',
        ];
    }

    public function getClientStatus(
        NetworkIntegration $integration,
        string $macAddress
    ): array {
        // Actual client status lookup will be implemented
        // using the verified Omada Controller API.

        return [
            'success' => false,
            'provider' => 'omada',
            'mac_address' => $macAddress,
            'message' => 'Omada client status lookup is not implemented yet.',
        ];
    }
}
