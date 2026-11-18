<?php

namespace Modules\Listings\Support;

/**
 * Canonical social profile links for a terrace.
 *
 * Only the public profile of a known platform is accepted: Facebook, Instagram,
 * TikTok and a Google Maps place link.
 */
final class SocialProfileLink
{
    /**
     * @var array<string, string>
     */
    public const NETWORKS = [
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'tiktok' => 'TikTok',
        'google_maps' => 'Google Maps',
    ];

    /**
     * @var array<string, string>
     */
    public const COLUMNS = [
        'facebook' => 'facebook_url',
        'instagram' => 'instagram_url',
        'tiktok' => 'tiktok_url',
        'google_maps' => 'google_maps_url',
    ];

    /**
     * @var array<string, string>
     */
    private const HINTS = [
        'facebook' => 'facebook.com o fb.com',
        'instagram' => 'instagram.com',
        'tiktok' => 'tiktok.com',
        'google_maps' => 'google.com/maps o maps.app.goo.gl',
    ];

    /**
     * Prepends the https scheme when the user writes a bare domain.
     */
    public static function normalize(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (! preg_match('#^https?://#i', $value)) {
            $value = 'https://'.$value;
        }

        return $value;
    }

    public static function isValid(string $network, string $url): bool
    {
        $parts = parse_url($url);

        if (! is_array($parts) || ! isset($parts['host'])) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));

        if (! in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        $host = strtolower((string) $parts['host']);
        $host = preg_replace('/^www\./', '', $host) ?? $host;
        $path = strtolower((string) ($parts['path'] ?? '/'));

        return match ($network) {
            'facebook' => self::hostMatches($host, ['facebook.com', 'fb.com']),
            'instagram' => self::hostMatches($host, ['instagram.com']),
            'tiktok' => self::hostMatches($host, ['tiktok.com']),
            'google_maps' => self::isGoogleMaps($host, $path),
            default => false,
        };
    }

    /**
     * @param  array<int, string>  $domains
     */
    private static function hostMatches(string $host, array $domains): bool
    {
        foreach ($domains as $domain) {
            if ($host === $domain || str_ends_with($host, '.'.$domain)) {
                return true;
            }
        }

        return false;
    }

    private static function isGoogleMaps(string $host, string $path): bool
    {
        if (in_array($host, ['maps.app.goo.gl', 'goo.gl', 'maps.google.com'], true)) {
            return true;
        }

        if (
            preg_match('/^google\.[a-z.]+$/', $host) === 1
            || str_ends_with($host, '.google.com')
        ) {
            return str_starts_with($path, '/maps');
        }

        return false;
    }

    public static function hint(string $network): string
    {
        return self::HINTS[$network] ?? '';
    }
}
