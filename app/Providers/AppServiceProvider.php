<?php

namespace App\Providers;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        $this->configurePlatformMailer();
    }

    private function configurePlatformMailer(): void
    {
        try {
            if (!Schema::hasTable('platform_settings')) {
                return;
            }

            $settings = PlatformSetting::query()
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

            if (($settings['mail_enabled'] ?? '0') !== '1') {
                return;
            }

            if (
                empty($settings['mail_host'])
                || empty($settings['mail_port'])
                || empty($settings['mail_from_address'])
            ) {
                return;
            }

            $password = '';

            if (!empty($settings['mail_password'])) {
                $password = Crypt::decryptString($settings['mail_password']);
            }

            $encryption = $settings['mail_encryption'] ?? 'tls';

            config([
                'mail.default' => 'smtp',

                'mail.mailers.smtp' => [
                    'transport' => 'smtp',
                    'scheme' => $encryption === 'ssl' ? 'smtps' : 'smtp',
                    'url' => null,
                    'host' => $settings['mail_host'],
                    'port' => (int) $settings['mail_port'],
                    'username' => $settings['mail_username'] ?: null,
                    'password' => $password ?: null,
                    'timeout' => 15,
                    'local_domain' => parse_url(
                        (string) config('app.url'),
                        PHP_URL_HOST
                    ),
                ],

                'mail.from.address' => $settings['mail_from_address'],
                'mail.from.name' => $settings['mail_from_name'] ?? 'NACPASI',
            ]);
        } catch (Throwable $exception) {
            // Keep the application's existing mail configuration if
            // platform SMTP settings are missing or cannot be decrypted.
            report($exception);
        }
    }
}
