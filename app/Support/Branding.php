<?php

namespace App\Support;

use App\Models\PlatformSetting;

final class Branding
{
    public static function settings(): PlatformSetting
    {
        return PlatformSetting::current();
    }

    public static function appName(): string
    {
        return self::settings()->app_name;
    }

    public static function fromAddress(): string
    {
        $from = self::settings()->from_address;

        return $from !== null && $from !== ''
            ? $from
            : (string) config('mail.from.address');
    }

    public static function supportEmail(): ?string
    {
        return self::settings()->support_email;
    }

    public static function logoUrl(): ?string
    {
        return self::settings()->logoUrl();
    }

    /**
     * @return array<string, string>
     */
    public static function legalLinks(): array
    {
        $settings = self::settings();

        return array_filter([
            'terms' => (string) ($settings->legal_terms_url ?? ''),
            'privacy' => (string) ($settings->legal_privacy_url ?? ''),
        ]);
    }
}
