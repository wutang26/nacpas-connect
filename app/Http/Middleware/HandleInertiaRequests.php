<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

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
     * Shared data available on every Inertia page.
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),

            'app' => [
                'name' => config('app.name', 'NACPAS Connect'),
                'url' => config('app.url'),
            ],

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

            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
        ];
    }
}
