<?php

namespace App\Services\Network;

use App\Models\NetworkIntegration;
use App\Services\Network\Adapters\OmadaAdapter;
use App\Services\Network\Contracts\NetworkAdapterInterface;
use InvalidArgumentException;

class NetworkManager
{
    /**
     * Resolve the appropriate network adapter.
     */
    public function adapter(
        NetworkIntegration $integration
    ): NetworkAdapterInterface {
        return match (strtolower($integration->provider)) {
            'omada' => app(OmadaAdapter::class),

            default => throw new InvalidArgumentException(
                "Unsupported network provider: {$integration->provider}"
            ),
        };
    }

    /**
     * Test a site's network integration.
     */
    public function testConnection(
        NetworkIntegration $integration
    ): array {
        return $this->adapter($integration)
            ->testConnection($integration);
    }

    /**
     * Authorize a client.
     */
    public function authorizeClient(
        NetworkIntegration $integration,
        \App\Models\WifiSession $session
    ): array {
        return $this->adapter($integration)
            ->authorizeClient($integration, $session);
    }

    /**
     * Disconnect a client.
     */
    public function disconnectClient(
        NetworkIntegration $integration,
        \App\Models\WifiSession $session
    ): array {
        return $this->adapter($integration)
            ->disconnectClient($integration, $session);
    }

    /**
     * Get client status.
     */
    public function getClientStatus(
        NetworkIntegration $integration,
        string $macAddress
    ): array {
        return $this->adapter($integration)
            ->getClientStatus($integration, $macAddress);
    }
}
