<?php

namespace App\Http\Middleware;

use App\Support\PlatformSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyPlatformSettings
{
    public function handle(Request $request, Closure $next): Response
    {
        $settings = PlatformSettings::all();

        $timezone = $settings['timezone'] ?? 'Africa/Dar_es_Salaam';

        // Apply the platform timezone to Laravel and PHP date functions.
        config(['app.timezone' => $timezone]);

        date_default_timezone_set($timezone);

        // Make the settings available to the next middleware.
        $request->attributes->set('platform_settings', $settings);

        return $next($request);
    }
}