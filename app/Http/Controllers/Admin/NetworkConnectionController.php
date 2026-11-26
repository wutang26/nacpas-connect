<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NetworkCredential;
use App\Models\NetworkIntegration;
use App\Models\WifiSession;
use App\Models\NetworkLog;
use App\Models\NetworkCommand;
use App\Services\Network\NetworkManager;
use App\Services\Network\Omada\OmadaSessionManager;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
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
        $canManageCredentials = auth()->user()
            ->can('network.credentials.manage');

        $integrations = NetworkIntegration::query()
            ->with([
                'organization:id,name',
                'site:id,name,slug',
            ])
            ->latest()
            ->get()
            ->map(function (NetworkIntegration $integration) use (
                $canManageCredentials
            ) {
                $credential = null;

                if ($integration->provider === 'omada') {
                    $credential = NetworkCredential::query()
                        ->where('site_id', $integration->site_id)
                        ->where('provider', 'omada')
                        ->where('credential_type', 'hotspot_operator')
                        ->where('status', 'active')
                        ->latest('id')
                        ->first();
                }

                return [
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

                    'api_region' => data_get(
                        $integration->settings,
                        'api_region'
                    ),

                    'controller_id' => $canManageCredentials
                        ? data_get($integration->settings, 'controller_id')
                        : null,

                    'credential_configured' => (bool) (
                        $credential &&
                        $credential->username &&
                        $credential->secret
                    ),

                    'credential_username' => $canManageCredentials
                        ? $credential?->username
                        : null,

                    'can_manage_credentials' => $canManageCredentials,
                ];
            });

        // Credentials: never select or expose the encrypted secret.
        $credentials = NetworkCredential::query()
            ->with(['site:id,name', 'device:id'])
            ->latest('id')
            ->limit(10)
            ->get([
                'id',
                'site_id',
                'device_id',
                'provider',
                'credential_type',
                'username',
                'status',
                'created_at',
            ])
            ->map(fn (NetworkCredential $credential) => [
                'id' => $credential->id,
                'provider' => $credential->provider,
                'credential_type' => $credential->credential_type,
                'username' => $credential->username,
                'status' => $credential->status,
                'site' => $credential->site?->name,
                'device_id' => $credential->device_id,
                'created_at' => $credential->created_at?->toIso8601String(),
            ]);

        $commands = NetworkCommand::query()
            ->with([
                'integration:id,name,provider',
                'site:id,name',
            ])
            ->latest('id')
            ->limit(10)
            ->get([
                'id',
                'network_integration_id',
                'site_id',
                'device_id',
                'wifi_session_id',
                'command_uuid',
                'command_type',
                'target_mac',
                'status',
                'attempts',
                'last_attempt_at',
                'executed_at',
                'created_at',
            ])
            ->map(fn (NetworkCommand $command) => [
                'id' => $command->id,
                'command_uuid' => $command->command_uuid,
                'command_type' => $command->command_type,
                'target_mac' => $command->target_mac,
                'status' => $command->status,
                'attempts' => $command->attempts,
                'site' => $command->site?->name,
                'integration' => $command->integration?->name,
                'device_id' => $command->device_id,
                'wifi_session_id' => $command->wifi_session_id,
                'last_attempt_at' => $command->last_attempt_at?->toIso8601String(),
                'executed_at' => $command->executed_at?->toIso8601String(),
                'created_at' => $command->created_at?->toIso8601String(),
            ]);

        $sessions = WifiSession::query()
            ->with(['site:id,name'])
            ->latest('id')
            ->limit(10)
            ->get([
                'id',
                'site_id',
                'subscription_id',
                'device_id',
                'client_mac',
                'client_ip',
                'started_at',
                'expires_at',
                'ended_at',
                'bytes_in',
                'bytes_out',
                'status',
                'created_at',
            ])
            ->map(fn (WifiSession $session) => [
                'id' => $session->id,
                'site' => $session->site?->name,
                'subscription_id' => $session->subscription_id,
                'device_id' => $session->device_id,
                'client_mac' => $session->client_mac,
                'client_ip' => $session->client_ip,
                'status' => $session->status,
                'started_at' => $session->started_at?->toIso8601String(),
                'expires_at' => $session->expires_at?->toIso8601String(),
                'ended_at' => $session->ended_at?->toIso8601String(),
                'bytes_in' => $session->bytes_in,
                'bytes_out' => $session->bytes_out,
            ]);

        $logs = NetworkLog::query()
            ->with(['site:id,name', 'device:id'])
            ->latest('occurred_at')
            ->limit(10)
            ->get([
                'id',
                'site_id',
                'device_id',
                'level',
                'event',
                'message',
                'occurred_at',
            ])
            ->map(fn (NetworkLog $log) => [
                'id' => $log->id,
                'site' => $log->site?->name,
                'device_id' => $log->device_id,
                'level' => $log->level,
                'event' => $log->event,
                // Do not expose the raw context JSON.
                'message' => $log->message
                    ? \Illuminate\Support\Str::limit(
                        preg_replace(
                            '/(password|secret|token|authorization)\\s*[:=]\\s*\\S+/i',
                            '$1=[REDACTED]',
                            $log->message
                        ),
                        240
                    )
                    : null,
                'occurred_at' => $log->occurred_at?->toIso8601String(),
            ]);

        $records = [
            'integrations' => $integrations->take(10)->values(),
            'credentials' => $credentials,
            'commands' => $commands,
            'sessions' => $sessions,
            'logs' => $logs,
            'counts' => [
                'integrations' => NetworkIntegration::count(),
                'credentials' => NetworkCredential::count(),
                'commands' => NetworkCommand::count(),
                'sessions' => WifiSession::count(),
                'logs' => NetworkLog::count(),
            ],
        ];

        return Inertia::render('NetworkConnections/Index', [
            'integrations' => $integrations,
            'canManageCredentials' => $canManageCredentials,
            'records' => $records,
        ]);
    }

    /**
     * Save Omada controller configuration and Hotspot Operator credentials.
     */
    public function updateOmadaSettings(
        Request $request,
        NetworkIntegration $integration,
        OmadaSessionManager $sessionManager
    ): RedirectResponse {
        abort_unless(
            $request->user()->can('network.credentials.manage'),
            403
        );

        abort_unless($integration->provider === 'omada', 404);

        $existingCredential = NetworkCredential::query()
            ->where('site_id', $integration->site_id)
            ->where('provider', 'omada')
            ->where('credential_type', 'hotspot_operator')
            ->latest('id')
            ->first();

        $passwordRules = (
            $existingCredential &&
            filled($existingCredential->secret)
        )
            ? ['nullable', 'string', 'max:1024']
            : ['required', 'string', 'min:1', 'max:1024'];

        $data = $request->validate([
            'controller_url' => [
                'required',
                'string',
                'url',
                'max:2048',
            ],

            'controller_version' => [
                'nullable',
                'string',
                'max:50',
            ],

            'controller_site_id' => [
                'nullable',
                'string',
                'max:255',
            ],

            'controller_id' => [
                'required',
                'string',
                'min:8',
                'max:128',
                'regex:/^[A-Za-z0-9_-]+$/',
            ],

            'api_region' => [
                'required',
                'string',
                'max:30',
                'regex:/^[a-z0-9-]+$/',
            ],

            'hotspot_username' => [
                'required',
                'string',
                'max:255',
            ],

            'hotspot_password' => $passwordRules,
        ]);

        $controllerUrl = rtrim($data['controller_url'], '/');

        $host = strtolower(
            (string) parse_url($controllerUrl, PHP_URL_HOST)
        );

        if (
            !str_starts_with($controllerUrl, 'https://') ||
            (
                $host !== 'tplinkcloud.com' &&
                !str_ends_with($host, '.tplinkcloud.com')
            )
        ) {
            throw ValidationException::withMessages([
                'controller_url' =>
                    'Tumia HTTPS Omada Cloud Controller URL halali ya tplinkcloud.com.',
            ]);
        }

        DB::transaction(function () use (
            $integration,
            $data,
            $controllerUrl,
            $existingCredential
        ) {
            $integration->refresh();

            $settings = $integration->settings ?? [];

            $settings['controller_id'] = $data['controller_id'];
            $settings['api_region'] = $data['api_region'];

            $integration->forceFill([
                'controller_url' => $controllerUrl,
                'controller_version' => $data['controller_version'] ?? null,
                'controller_site_id' => $data['controller_site_id'] ?: null,
                'settings' => $settings,

                // Previous authentication no longer verifies
                // the newly saved configuration.
                'status' => 'pending',
                'last_connected_at' => null,
                'last_error' => null,
            ])->save();

            $credential = $existingCredential
                ? NetworkCredential::query()->findOrFail(
                    $existingCredential->id
                )
                : new NetworkCredential();

            $credential->site_id = $integration->site_id;
            $credential->device_id = null;
            $credential->provider = 'omada';
            $credential->credential_type = 'hotspot_operator';
            $credential->username = $data['hotspot_username'];
            $credential->status = 'active';

            // Empty password means retain the existing encrypted secret.
            if (
                isset($data['hotspot_password']) &&
                $data['hotspot_password'] !== ''
            ) {
                // NetworkCredential model encrypts this automatically.
                $credential->secret = $data['hotspot_password'];
            }

            $credential->save();
        });

        // Remove the previous cached authentication session.
        $sessionManager->invalidate($integration);

        return back()->with('success', 'Omada configuration saved successfully.');
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