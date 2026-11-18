<?php

namespace Modules\Listings\Support;

use App\Support\Branding;
use Illuminate\Support\Str;
use Modules\Listings\Enums\ListingMediaType;
use Modules\Listings\Models\Listing;
use Modules\Listings\Models\ListingMedia;

/**
 * Builds the SEO payload (meta tags and JSON-LD) for a public listing page.
 */
final class ListingSeo
{
    /**
     * @return array{
     *     title: string,
     *     description: string,
     *     canonical: string,
     *     image: string|null,
     *     robots: string,
     *     type: string,
     *     site_name: string,
     *     json_ld: array<string, mixed>
     * }
     */
    public static function forListing(Listing $listing): array
    {
        $description = self::description($listing);
        $canonical = route('public.listings.show', ['listing' => $listing->slug]);
        $images = $listing->media
            ->filter(fn (ListingMedia $media): bool => $media->type === ListingMediaType::Image)
            ->map(fn (ListingMedia $media): string => url($media->url))
            ->values()
            ->all();

        return [
            'title' => "{$listing->name} · Terrazas para eventos",
            'description' => $description,
            'canonical' => $canonical,
            'image' => $images[0] ?? null,
            'robots' => 'index, follow',
            'type' => 'website',
            'site_name' => Branding::appName(),
            'json_ld' => self::jsonLd($listing, $canonical, $description, $images),
        ];
    }

    private static function description(Listing $listing): string
    {
        $text = trim((string) preg_replace(
            '/\s+/',
            ' ',
            strip_tags((string) $listing->description),
        ));

        if ($text === '') {
            $location = trim(implode(', ', array_filter([$listing->city, $listing->state])));

            $text = $location === ''
                ? "{$listing->name}: terraza para eventos."
                : "{$listing->name}: terraza para eventos en {$location}.";
        }

        return Str::limit($text, 155);
    }

    /**
     * @param  array<int, string>  $images
     * @return array<string, mixed>
     */
    private static function jsonLd(
        Listing $listing,
        string $canonical,
        string $description,
        array $images,
    ): array {
        $claimable = $listing->isClaimable();

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'EventVenue',
            'name' => $listing->name,
            'description' => $description,
            'url' => $canonical,
        ];

        if ($images !== []) {
            $data['image'] = $images;
        }

        $address = array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => $listing->address,
            'addressLocality' => $listing->city,
            'addressRegion' => $listing->state,
            'postalCode' => $listing->postal_code,
            'addressCountry' => $listing->country,
        ], fn (mixed $value): bool => $value !== null && $value !== '');

        if (count($address) > 1) {
            $data['address'] = $address;
        }

        if ($listing->latitude !== null && $listing->longitude !== null) {
            $data['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $listing->latitude,
                'longitude' => (float) $listing->longitude,
            ];
        }

        if ($listing->capacity !== null) {
            $data['maximumAttendeeCapacity'] = $listing->capacity;
        }

        if (! $claimable) {
            if (filled($listing->contact_phone)) {
                $data['telephone'] = $listing->contact_phone;
            }

            if (filled($listing->contact_email)) {
                $data['email'] = $listing->contact_email;
            }
        }

        $social = array_values(array_map(
            fn (array $link): string => $link['url'],
            ListingPresenter::socialLinks($listing),
        ));

        if ($social !== []) {
            $data['sameAs'] = $social;
        }

        if ($listing->price_from_cents !== null && $listing->price_from_cents > 0) {
            $data['priceRange'] = '$'.number_format($listing->price_from_cents / 100, 0, '.', ',').' '.$listing->currency;
        }

        return $data;
    }
}
