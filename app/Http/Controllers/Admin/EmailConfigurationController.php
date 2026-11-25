<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class EmailConfigurationController extends Controller
{
    private function authorizeView(Request $request): void
    {
        $user = $request->user();

        abort_unless(
            $user->hasRole('super_admin')
                || $user->can('platform.settings.view')
                || $user->can('platform.settings.manage'),
            403,
            'Huna ruhusa ya kuona Email Configuration.'
        );
    }

    private function authorizeManage(Request $request): void
    {
        $user = $request->user();

        abort_unless(
            $user->hasRole('super_admin')
                || $user->can('platform.settings.manage'),
            403,
            'Huna ruhusa ya kubadilisha Email Configuration.'
        );
    }

    private function savedSettings(): array
    {
        return PlatformSetting::query()
            ->whereIn('key', [
                'mail_enabled',
                'mail_host',
                'mail_port',
                'mail_username',
                'mail_password',
                'mail_encryption',
                'mail_from_address',
                'mail_from_name',
            ])
            ->pluck('value', 'key')
            ->all();
    }

    public function index(Request $request): Response
    {
        $this->authorizeView($request);

        $saved = $this->savedSettings();

        return Inertia::render('Settings/EmailConfiguration', [
            'emailSettings' => [
                'enabled' => ($saved['mail_enabled'] ?? '0') === '1',
                'host' => $saved['mail_host'] ?? '',
                'port' => $saved['mail_port'] ?? '587',
                'username' => $saved['mail_username'] ?? '',
                'encryption' => $saved['mail_encryption'] ?? 'tls',
                'from_address' => $saved['mail_from_address'] ?? '',
                'from_name' => $saved['mail_from_name'] ?? 'NACPASI',
                'password_configured' => !empty($saved['mail_password']),
            ],
            'canManageEmailSettings' => $request->user()->hasRole('super_admin')
                || $request->user()->can('platform.settings.manage'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
            'host' => [
                Rule::requiredIf($request->boolean('enabled')),
                'nullable',
                'string',
                'max:255',
            ],
            'port' => [
                Rule::requiredIf($request->boolean('enabled')),
                'nullable',
                'integer',
                'between:1,65535',
            ],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:500'],
            'encryption' => ['required', Rule::in(['tls', 'ssl', 'none'])],
            'from_address' => [
                Rule::requiredIf($request->boolean('enabled')),
                'nullable',
                'email',
                'max:150',
            ],
            'from_name' => ['required', 'string', 'max:150'],
        ]);

        $settings = [
            'mail_enabled' => $validated['enabled'] ? '1' : '0',
            'mail_host' => $validated['host'] ?? '',
            'mail_port' => (string) ($validated['port'] ?? 587),
            'mail_username' => $validated['username'] ?? '',
            'mail_encryption' => $validated['encryption'],
            'mail_from_address' => $validated['from_address'] ?? '',
            'mail_from_name' => $validated['from_name'],
        ];

        foreach ($settings as $key => $value) {
            PlatformSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'group_name' => 'email',
                ]
            );
        }

        // Empty password means retain the existing encrypted password.
        if (!empty($validated['password'])) {
            PlatformSetting::updateOrCreate(
                ['key' => 'mail_password'],
                [
                    'value' => Crypt::encryptString($validated['password']),
                    'group_name' => 'email',
                ]
            );
        }

        return redirect()
            ->route('settings.email.index')
            ->with('success', 'Email Configuration imehifadhiwa kikamilifu.');
    }

    public function test(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);

        $validated = Validator::make($request->all(), [
            'email' => ['required', 'email', 'max:150'],
        ])->validate();

        $saved = $this->savedSettings();

        if (($saved['mail_enabled'] ?? '0') !== '1') {
            return back()->with('error', 'Washa SMTP kwanza kabla ya kutuma test email.');
        }

        if (
            empty($saved['mail_host'])
            || empty($saved['mail_port'])
            || empty($saved['mail_from_address'])
        ) {
            return back()->with('error', 'SMTP configuration haijakamilika.');
        }

        try {
            $password = '';

            if (!empty($saved['mail_password'])) {
                $password = Crypt::decryptString($saved['mail_password']);
            }

            $encryption = $saved['mail_encryption'] ?? 'tls';

            config([
                'mail.mailers.smtp' => [
                    'transport' => 'smtp',
                    'scheme' => $encryption === 'ssl' ? 'smtps' : 'smtp',
                    'url' => null,
                    'host' => $saved['mail_host'],
                    'port' => (int) $saved['mail_port'],
                    'username' => $saved['mail_username'] ?: null,
                    'password' => $password ?: null,
                    'timeout' => 15,
                    'local_domain' => parse_url(
                        (string) config('app.url'),
                        PHP_URL_HOST
                    ),
                ],
                'mail.from.address' => $saved['mail_from_address'],
                'mail.from.name' => $saved['mail_from_name'] ?? 'NACPASI',
            ]);

            Mail::purge('smtp');

            Mail::mailer('smtp')
                ->raw(
                    "Hongera! SMTP configuration ya NACPASI inafanya kazi.\n\n"
                    . "Test email imetumwa kikamilifu.\n"
                    . "Sender: " . ($saved['mail_from_name'] ?? 'NACPASI'),
                    function ($message) use ($validated, $saved) {
                        $message
                            ->to($validated['email'])
                            ->subject('NACPASI - SMTP Test Email');
                    }
                );

            return back()->with(
                'success',
                'Test email imetumwa. Angalia inbox ya ' . $validated['email'] . '.'
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'error',
                'Test email imeshindikana. Hakiki SMTP host, port, encryption na credentials.'
            );
        }
    }
}
