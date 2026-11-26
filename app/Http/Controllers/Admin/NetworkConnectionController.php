<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NetworkIntegration;
use App\Services\Network\NetworkManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class NetworkConnectionController extends Controller
{
    /**
     * Display configured network controller integrations.
     */
    public function index(): Response
    {
        $integrations = NetworkIntegration::query()
            ->with([
                'organization:id,name',
                'site:id,name,slug',
            ])
            ->latest()
            ->get()
            ->map(fn (NetworkIntegration $integration) => [
                'id' => $integration->id,
                'provider' => $integration->provider,
                'name' => $integration->name,
                'controller_url' => $integration->controller_url,
                'controller_version' => $integration->controller_version,
                'controller_site_id' => $integration->controller_site_id,
                'status' => $integration->status,
                'last_connected_at' => $integration->last_connected_at
                    ? $integration->last_connected_at->toIso8601String()
                    : null,
                'last_error' => $integration->last_error,
                'organization' => [
                    'id' => $integration->organization?->id,
                    'name' => $integration->organization?->name,
                ],
                'site' => [
                    'id' => $integration->site?->id,
                    'name' => $integration->site?->name,
                    'slug' => $integration->site?->slug,
                ],
                'controller_id_configured' => !empty(
                    data_get($integration->settings, 'controller_id')
                ),
            ]);

        return Inertia::render('NetworkConnections/Index', [
            'integrations' => $integrations,
        ]);
    }

    /**
     * Perform a fresh authentication test against a network controller.
     */
    public function test(
        NetworkIntegration $integration,
        NetworkManager $networkManager
    ): RedirectResponse {
        $startedAt = hrtime(true);

        try {
            $result = $networkManager->testConnection($integration);

            $latencyMs = round(
                (hrtime(true) - $startedAt) / 1_000_000,
                2
            );

            $result = [
                'integration_id' => $integration->id,
                'provider' => $integration->provider,
                'success' => (bool) ($result['success'] ?? false),
                'message' => $result['message'] ?? 'Connection test completed.',
                'test_type' => 'fresh_api_authentication',
                'latency_ms' => $latencyMs,
                'tested_at' => now()->toIso8601String(),
                'controller_version' => $integration->controller_version,
            ];

            return back()->with('connection_test', $result);
        } catch (Throwable $exception) {
            report($exception);

            $integration->forceFill([
                'status' => 'error',
                'last_error' => 'Connection test could not be completed. Check server logs.',
            ])->save();

            Log::warning('Network connection test failed.', [
                'integration_id' => $integration->id,
                'provider' => $integration->provider,
                'exception' => get_class($exception),
            ]);

            return back()->with('connection_test', [
                'integration_id' => $integration->id,
                'provider' => $integration->provider,
                'success' => false,
                'message' => 'Connection test could not be completed. Check the integration configuration and server logs.',
                'test_type' => 'fresh_api_authentication',
                'latency_ms' => round(
                    (hrtime(true) - $startedAt) / 1_000_000,
                    2
                ),
                'tested_at' => now()->toIso8601String(),
                'controller_version' => $integration->controller_version,
            ]);
        }
    }
}
