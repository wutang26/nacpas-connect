<?php

namespace App\Services\Network\Contracts;

use App\Models\NetworkIntegration;
use App\Models\WifiSession;

interface NetworkAdapterInterface
{
    /**
     * Check whether the network controller is reachable
     * and authentication is successful.
     */
    public function testConnection(
        NetworkIntegration $integration
    ): array;

    /**
     * Authorize a customer's device on the network.
     */
    public function authorizeClient(
        NetworkIntegration $integration,
        WifiSession $session
    ): array;

    /**
     * Disconnect a customer's device from the network.
     */
    public function disconnectClient(
        NetworkIntegration $integration,
        WifiSession $session
    ): array;

    /**
     * Retrieve client status from the network controller.
     */
    public function getClientStatus(
        NetworkIntegration $integration,
        string $macAddress
    ): array;
}
