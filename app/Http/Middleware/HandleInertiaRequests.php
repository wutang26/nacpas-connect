<?php

namespace App\Http\Middleware;

use App\Support\PlatformSettings;
use Closure;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Symfony\Component\HttpFoundation\Response;

class HandleInertiaRequests extends Middleware
{
    /**
     * Root Blade template.
     */
    protected $rootView = 'app';

    /**
     * Asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Apply platform timezone before controllers execute.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $settings = PlatformSettings::all();

        // Keep settings available for the share() method.
        $request->attributes->set('platform_settings', $settings);

        // $timezone = $settings['timezone'] ?? 'Africa/Dar_es_Salaam';

        // // Only accept valid PHP timezone identifiers.
        // if (
        //     is_string($timezone) &&
        //     in_array($timezone, timezone_identifiers_list(), true)
        // ) {
        //     config(['app.timezone' => $timezone]);

        //     date_default_timezone_set($timezone);
        // }
            
        $timezone = $settings['timezone'] ?? 'Africa/Dar_es_Salaam';

        $validTimezone = is_string($timezone)
            && in_array(
                $timezone,
                \DateTimeZone::listIdentifiers(),
                true
            );

        if (!$validTimezone) {
            $timezone = 'Africa/Dar_es_Salaam';
        }

        config(['app.timezone' => $timezone]);

        date_default_timezone_set($timezone);

        return parent::handle($request, $next);
    }

    /**
     * Shared data available on every Inertia page.
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        $settings = $request->attributes->get(
            'platform_settings',
            []
        );

        $platformName = $settings['platform_name'] ?? 'NACPASI';

        return [
            ...parent::share($request),

            /*
            |--------------------------------------------------------------------------
            | Application Information
            |--------------------------------------------------------------------------
            */
            'app' => [
                'name' => $platformName,
                'url' => config('app.url'),
            ],

            /*
            |--------------------------------------------------------------------------
            | Platform Settings
            |--------------------------------------------------------------------------
            */
            'platformSettings' => [
                'platform_name' => $platformName,

                'platform_tagline' => $settings['platform_tagline']
                    ?? 'Connect. Manage. Grow.',

                'support_email' => $settings['support_email'] ?? '',

                'support_phone' => $settings['support_phone'] ?? '',

                'company_name' => $settings['company_name'] ?? '',

                'company_address' => $settings['company_address'] ?? '',

                'company_website' => $settings['company_website'] ?? '',

                'country' => $settings['country'] ?? 'Tanzania',

                'currency' => $settings['currency'] ?? 'TZS',

                'timezone' => $settings['timezone']
                    ?? 'Africa/Dar_es_Salaam',

                'date_format' => $settings['date_format'] ?? 'd/m/Y',
            ],

            /*
            |--------------------------------------------------------------------------
            | Authentication
            |--------------------------------------------------------------------------
            */
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,

                    'name' => $user->name,

                    'email' => $user->email,

                    'roles' => $user->getRoleNames()->values(),

                    'permissions' => $user->getAllPermissions()
                        ->pluck('name')
                        ->values(),
                ] : null,
            ],

            /*
            |--------------------------------------------------------------------------
            | Flash Messages
            |--------------------------------------------------------------------------
            */
            'flash' => [
                'success' => fn () => $request->session()->get('success'),

                'error' => fn () => $request->session()->get('error'),

                'warning' => fn () => $request->session()->get('warning'),

                'info' => fn () => $request->session()->get('info'),
            ],
        ];
    }
}
