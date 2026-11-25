<?php

namespace App\Services\Network;

use App\Models\Subscription;
use App\Models\WifiSession;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DeviceClaimService
{
    /**
     * Register a verified client device against a paid subscription.
     *
     * The caller must obtain the MAC/IP from a trusted network source.
     * This service does not assume any Omada callback parameters.
     */
    public function claim(
        string $accessToken,
        string $clientMac,
        ?string $clientIp = null
    ): WifiSession {
        $accessToken = trim($accessToken);
        $clientMac = $this->normalizeMac($clientMac);
        $clientIp = $this->normalizeIp($clientIp);

        if ($accessToken === '') {
            throw new InvalidArgumentException(
                'A valid subscription access token is required.'
            );
        }

        $tokenHash = hash('sha256', $accessToken);

        return DB::transaction(function () use (
            $tokenHash,
            $clientMac,
            $clientIp
        ) {
            /*
             * Lock the subscription so simultaneous claims for the
             * same subscription are serialized on supported databases.
             */
            $subscription = Subscription::query()
                ->where('access_token_hash', $tokenHash)
                ->lockForUpdate()
                ->first();

            if (! $subscription) {
                throw new InvalidArgumentException(
                    'Invalid subscription access token.'
                );
            }

            if ($subscription->status !== 'active') {
                throw new InvalidArgumentException(
                    'The subscription is not active.'
                );
            }

            if (
                ! $subscription->expires_at ||
                $subscription->expires_at->lessThanOrEqualTo(now())
            ) {
                throw new InvalidArgumentException(
                    'The subscription has expired.'
                );
            }

            /*
             * A successful payment must have a confirmed paid_at value.
             * Checkout initiation alone is not payment confirmation.
             */
            $hasConfirmedPayment = $subscription->payments()
                ->where('status', 'successful')
                ->whereNotNull('paid_at')
                ->exists();

            if (! $hasConfirmedPayment) {
                throw new InvalidArgumentException(
                    'The subscription does not have a confirmed successful payment.'
                );
            }

            /*
             * Always use the device limit captured at purchase time.
             */
            $snapshot = $subscription->package_snapshot ?? [];
            $deviceLimit = (int) ($snapshot['device_limit'] ?? 0);

            if ($deviceLimit < 1) {
                throw new InvalidArgumentException(
                    'The subscription has an invalid device limit.'
                );
            }

            /*
             * Idempotency: the same MAC on this subscription returns
             * its existing session instead of creating another one.
             */
            $existingSession = $subscription->wifiSessions()
                ->where('client_mac', $clientMac)
                ->first();

            if ($existingSession) {
                return $existingSession;
            }

            /*
             * Count all previously registered MAC addresses.
             * Disconnected sessions remain registered devices.
             */
            $registeredDevices = $subscription->wifiSessions()
                ->whereNotNull('client_mac')
                ->count();

            if ($registeredDevices >= $deviceLimit) {
                throw new InvalidArgumentException(
                    'The subscription has reached its device limit.'
                );
            }

            /*
             * Create a pending session. Network authorization is a
             * separate operation and is intentionally not performed here.
             */
            return WifiSession::create([
                'site_id' => $subscription->site_id,
                'subscription_id' => $subscription->id,
                'customer_id' => $subscription->customer_id,
                'client_mac' => $clientMac,
                'client_ip' => $clientIp,
                'started_at' => null,
                'expires_at' => $subscription->expires_at,
                'ended_at' => null,
                'bytes_in' => 0,
                'bytes_out' => 0,
                'status' => 'pending',
            ]);
        }, 3);
    }

    /**
     * Normalize and validate a standard EUI-48 MAC address.
     */
    private function normalizeMac(string $mac): string
    {
        $mac = strtoupper(trim($mac));
        $mac = str_replace('-', ':', $mac);

        if (! preg_match(
            '/^(?:[0-9A-F]{2}:){5}[0-9A-F]{2}$/',
            $mac
        )) {
            throw new InvalidArgumentException(
                'A valid client MAC address is required.'
            );
        }

        return $mac;
    }

    /**
     * Validate an optional IPv4 or IPv6 client address.
     */
    private function normalizeIp(?string $ip): ?string
    {
        if ($ip === null || trim($ip) === '') {
            return null;
        }

        $ip = trim($ip);

        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            throw new InvalidArgumentException(
                'The client IP address is invalid.'
            );
        }

        return $ip;
    }
}
