<?php

namespace App\Services\Payments;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FastLipaService
{
    protected function baseUrl(): string
    {
        return rtrim(
            config('services.fastlipa.base_url'),
            '/'
        );
    }

    protected function apiKey(): string
    {
        $key = config('services.fastlipa.api_key');

        if (!$key) {
            throw new RuntimeException(
                'FastLipa API key haijawekwa.'
            );
        }

        return $key;
    }

    /**
     * Initiate a mobile money USSD push.
     *
     * @throws ConnectionException
     */
    public function createTransaction(array $payload): array
    {
        $response = Http::acceptJson()
            ->asJson()
            ->withToken($this->apiKey())
            ->timeout(20)
            ->post(
                $this->baseUrl() . '/transaction/create',
                $payload
            );

        return [
            'http_status' => $response->status(),
            'successful' => $response->successful(),
            'body' => $response->json(),
        ];
    }

    /**
     * Retrieve transaction status from FastLipa.
     *
     * @throws ConnectionException
     */
    public function transactionStatus(string $tranId): array
    {
        $response = Http::acceptJson()
            ->withToken($this->apiKey())
            ->timeout(15)
            ->get(
                $this->baseUrl() . '/transaction/status',
                ['tranid' => $tranId]
            );

        return [
            'http_status' => $response->status(),
            'successful' => $response->successful(),
            'body' => $response->json(),
        ];
    }
}
