<?php

namespace Modules\Bookings\Support;

use Illuminate\Http\Request;
use Modules\Listings\Models\Listing;

final class LeadAttribution
{
    /**
     * Capture UTM/referrer data for a listing into the session.
     *
     * @return array<string, string>
     */
    public static function capture(Request $request, Listing $listing): array
    {
        $utmSource = self::string($request->query('utm_source'));
        $referrer = self::string($request->headers->get('referer'));

        $attribution = array_filter([
            'source_detail' => $utmSource ?? self::referrerHost($referrer),
            'utm_source' => $utmSource,
            'utm_medium' => self::string($request->query('utm_medium')),
            'utm_campaign' => self::string($request->query('utm_campaign')),
            'utm_term' => self::string($request->query('utm_term')),
            'utm_content' => self::string($request->query('utm_content')),
            'referrer' => $referrer,
        ], fn (?string $value): bool => $value !== null && $value !== '');

        if ($attribution !== []) {
            $request->session()->put(self::key($listing), $attribution);
        }

        return $attribution;
    }

    /**
     * @return array<string, string>
     */
    public static function resolve(Request $request, Listing $listing): array
    {
        /** @var array<string, string> $stored */
        $stored = $request->session()->get(self::key($listing), []);

        return $stored;
    }

    private static function key(Listing $listing): string
    {
        return "booking_attribution.{$listing->id}";
    }

    private static function string(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : mb_substr($value, 0, 500);
    }

    private static function referrerHost(?string $referrer): ?string
    {
        if ($referrer === null) {
            return null;
        }

        $host = parse_url($referrer, PHP_URL_HOST);
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (! is_string($host) || $host === '' || $host === $appHost) {
            return null;
        }

        return $host;
    }
}
