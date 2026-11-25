<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlatformSettingController extends Controller
{
    private function defaults(): array
    {
        return [
            'platform_name' => 'NACPASI',
            'platform_tagline' => 'Connect. Manage. Grow.',
            'support_email' => '',
            'support_phone' => '',
            'company_name' => '',
            'company_address' => '',
            'company_website' => '',
            'country' => 'Tanzania',
            'currency' => 'TZS',
            'timezone' => 'Africa/Dar_es_Salaam',
            'date_format' => 'd/m/Y',
        ];
    }

    private function settingGroups(): array
    {
        return [
            'platform_name' => 'general',
            'platform_tagline' => 'general',
            'support_email' => 'general',
            'support_phone' => 'general',
            'company_name' => 'company',
            'company_address' => 'company',
            'company_website' => 'company',
            'country' => 'regional',
            'currency' => 'regional',
            'timezone' => 'regional',
            'date_format' => 'regional',
        ];
    }

    private function authorizeView(Request $request): void
    {
        $user = $request->user();

        abort_unless(
            $user->hasRole('super_admin')
            || $user->can('platform.settings.view')
            || $user->can('platform.settings.manage'),
            403,
            'Huna ruhusa ya kuona Platform Settings.'
        );
    }

    private function authorizeManage(Request $request): void
    {
        $user = $request->user();

        abort_unless(
            $user->hasRole('super_admin')
            || $user->can('platform.settings.manage'),
            403,
            'Huna ruhusa ya kubadilisha Platform Settings.'
        );
    }

    public function index(Request $request): Response
    {
        $this->authorizeView($request);

        $settings = array_replace(
            $this->defaults(),
            PlatformSetting::query()->pluck('value', 'key')->all()
        );

        return Inertia::render('Settings/Index', [
            'settings' => $settings,
            'canManageSettings' => $request->user()->hasRole('super_admin')
                || $request->user()->can('platform.settings.manage'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'settings' => ['required', 'array'],

            'settings.platform_name' => ['required', 'string', 'max:100'],
            'settings.platform_tagline' => ['nullable', 'string', 'max:180'],
            'settings.support_email' => ['nullable', 'email', 'max:150'],
            'settings.support_phone' => ['nullable', 'string', 'max:30'],

            'settings.company_name' => ['nullable', 'string', 'max:150'],
            'settings.company_address' => ['nullable', 'string', 'max:500'],
            'settings.company_website' => ['nullable', 'url', 'max:200'],

            'settings.country' => ['required', 'string', 'max:100'],
            'settings.currency' => ['required', 'in:TZS,USD,KES,UGX'],
            'settings.timezone' => ['required', 'timezone'],
            'settings.date_format' => ['required', 'in:d/m/Y,Y-m-d,m/d/Y'],
        ]);

        $groups = $this->settingGroups();

        foreach ($validated['settings'] as $key => $value) {
            if (!array_key_exists($key, $groups)) {
                continue;
            }

            PlatformSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'group_name' => $groups[$key],
                ]
            );
        }

        return redirect()
            ->route('settings.index')
            ->with('success', 'Platform Settings zimehifadhiwa kikamilifu.');
    }
}
