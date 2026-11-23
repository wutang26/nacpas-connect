<?php

namespace App\Services\Network\Omada;

use App\Models\NetworkCredential;
use App\Models\NetworkIntegration;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class OmadaSessionManager
{
    /**
     * Get an existing Omada session or create a new one.
     */
    public function getSession(NetworkIntegration $integration): array
    {
        $cached = $this->readCachedSession($integration);

        if ($cached !== null) {
            return $cached;
        }

        $lockKey = $this->lockKey($integration);

        return Cache::lock($lockKey, 30)->block(15, function () use ($integration) {
            // Check again after acquiring the lock.
            $cached = $this->readCachedSession($integration);

            if ($cached !== null) {
                return $cached;
            }

            return $this->login($integration);
        });
    }

    /**
     * Force a new Omada login.
     */
    public function refresh(NetworkIntegration $integration): array
    {
        $this->invalidate($integration);

        return $this->getSession($integration);
    }

    /**
     * Remove the cached Omada session.
     */
    public function invalidate(NetworkIntegration $integration): void
    {
        Cache::forget($this->cacheKey($integration));
    }

    /**
     * Authenticate with Omada and securely cache the session.
     */
    private function login(NetworkIntegration $integration): array
    {
        $credential = NetworkCredential::query()
            ->where('site_id', $integration->site_id)
            ->where('provider', 'omada')
            ->where('credential_type', 'hotspot_operator')
            ->where('status', 'active')
            ->latest('id')
            ->first();

        if (
            !$credential ||
            !$credential->username ||
            !$credential->secret
        ) {
            throw new RuntimeException(
                'Active Omada Hotspot Operator credentials were not found.'
            );
        }

        $settings = $integration->settings ?? [];
        $controllerId = $settings['controller_id'] ?? null;

        if (!$controllerId) {
            throw new RuntimeException(
                'Omada Controller ID is missing.'
            );
        }

        $baseUrl = rtrim($integration->controller_url, '/');

        if (!str_starts_with($baseUrl, 'https://')) {
            throw new RuntimeException(
                'Omada API must use HTTPS.'
            );
        }

        $cookieJar = new CookieJar();

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout(25)
                ->withOptions([
                    'cookies' => $cookieJar,
                ])
                ->post(
                    "{$baseUrl}/{$controllerId}/api/v2/hotspot/login",
                    [
                        'name' => $credential->username,
                        'password' => $credential->secret,
                    ]
                );

            if (!$response->successful()) {
                throw new RuntimeException(
                    'Omada API returned HTTP '.$response->status().'.'
                );
            }

            $data = $response->json();

            if (
                !is_array($data) ||
                (int) ($data['errorCode'] ?? -1) !== 0
            ) {
                throw new RuntimeException(
                    'Omada authentication failed.'
                );
            }

            $csrfToken = data_get($data, 'result.token');

            $sessionCookie = $cookieJar->getCookieByName(
                'TPOMADA_SESSIONID'
            );

            if (!$csrfToken || !$sessionCookie) {
                throw new RuntimeException(
                    'Omada did not return the required session data.'
                );
            }

            $cookieValue = $sessionCookie->getValue();

            if (!$cookieValue) {
                throw new RuntimeException(
                    'Omada returned an invalid session cookie.'
                );
            }

            // Respect the cookie expiry supplied by Omada.
            // If it is a session cookie without an expiry,
            // use a conservative 10-minute cache lifetime.
            $expiresAt = $sessionCookie->getExpires();

            $ttl = $expiresAt
                ? $expiresAt - time()
                : 600;

            if ($ttl <= 0) {
                throw new RuntimeException(
                    'Omada session cookie has already expired.'
                );
            }

            // Do not cache this session for more than one hour.
            $ttl = min($ttl, 3600);

            Cache::put(
                $this->cacheKey($integration),
                [
                    'csrf_token' => Crypt::encryptString($csrfToken),
                    'session_cookie' => Crypt::encryptString($cookieValue),
                ],
                now()->addSeconds($ttl)
            );

            return [
                'csrf_token' => $csrfToken,
                'session_cookie' => $cookieValue,
            ];
        } catch (RuntimeException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            // Never expose credentials, tokens or cookie values.
            throw new RuntimeException(
                'Unable to authenticate with Omada. Check server connectivity.'
            );
        }
    }

    /**
     * Read and decrypt the cached session.
     */
    private function readCachedSession(
        NetworkIntegration $integration
    ): ?array {
        $cached = Cache::get($this->cacheKey($integration));

        if (!is_array($cached)) {
            return null;
        }

        try {
            $csrfToken = Crypt::decryptString(
                $cached['csrf_token'] ?? ''
            );

            $sessionCookie = Crypt::decryptString(
                $cached['session_cookie'] ?? ''
            );

            if (!$csrfToken || !$sessionCookie) {
                $this->invalidate($integration);

                return null;
            }

            return [
                'csrf_token' => $csrfToken,
                'session_cookie' => $sessionCookie,
            ];
        } catch (Throwable $exception) {
            $this->invalidate($integration);

            return null;
        }
    }

    private function cacheKey(NetworkIntegration $integration): string
    {
        return 'nacpasi:omada:session:'.$integration->id;
    }

    private function lockKey(NetworkIntegration $integration): string
    {
        return 'nacpasi:omada:session-lock:'.$integration->id;
    }
}
