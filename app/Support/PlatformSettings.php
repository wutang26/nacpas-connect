<?php

namespace App\Support;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Schema;

class PlatformSettings
{
    /**
     * Default platform settings.
     */
    private const DEFAULTS = [
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

    /**
     * Get one platform setting.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $fallback = $default ?? self::DEFAULTS[$key] ?? null;

        try {
            if (!Schema::hasTable('platform_settings')) {
                return $fallback;
            }

            return PlatformSetting::query()
                ->where('key', $key)
                ->value('value') ?? $fallback;
        } catch (\Throwable $exception) {
            report($exception);

            return $fallback;
        }
    }

    /**
     * Get all platform settings merged with defaults.
     */
    public static function all(): array
    {
        $settings = self::DEFAULTS;

        try {
            if (!Schema::hasTable('platform_settings')) {
                return $settings;
            }

            $storedSettings = PlatformSetting::query()
                ->pluck('value', 'key')
                ->all();

            return array_replace($settings, $storedSettings);
        } catch (\Throwable $exception) {
            report($exception);

            return $settings;
        }
    }
}
