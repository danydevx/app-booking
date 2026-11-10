<?php

namespace Modules\Listings\Support;

use App\Support\WhatsApp;
use Modules\Listings\Models\Listing;
use Modules\Listings\Models\ListingMedia;
use Modules\Listings\Models\ListingPackage;
use Modules\Listings\Models\ListingPromotion;
use Modules\Listings\Models\ListingService;

final class ListingPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function ownerCard(Listing $listing, string $teamSlug): array
    {
        return [
            'id' => $listing->id,
            'name' => $listing->name,
            'slug' => $listing->slug,
            'status' => $listing->status->value,
            'status_label' => $listing->status->label(),
            'city' => $listing->city,
            'state' => $listing->state,
            'cover_url' => $listing->coverImage?->url,
            'images_count' => (int) ($listing->images_count ?? 0),
            'videos_count' => (int) ($listing->videos_count ?? 0),
            'public_url' => $listing->isPublished() ? self::publicUrl($listing) : null,
            'manage_url' => route('listings.manage', [
                'current_team' => $teamSlug,
                'listing' => $listing->slug,
            ]),
            'edit_url' => route('listings.edit', [
                'current_team' => $teamSlug,
                'listing' => $listing->slug,
            ]),
            'updated_at' => $listing->updated_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function ownerForm(Listing $listing, string $teamSlug): array
    {
        return [
            'id' => $listing->id,
            'name' => $listing->name,
            'slug' => $listing->slug,
            'description' => $listing->description,
            'address' => $listing->address,
            'city' => $listing->city,
            'state' => $listing->state,
            'country' => $listing->country,
            'postal_code' => $listing->postal_code,
            'latitude' => $listing->latitude,
            'longitude' => $listing->longitude,
            'capacity' => $listing->capacity,
            'services' => $listing->services ?? [],
            'details' => $listing->details ?? [],
            'rules' => $listing->rules,
            'contact_name' => $listing->contact_name,
            'contact_email' => $listing->contact_email,
            'contact_phone' => $listing->contact_phone,
            'whatsapp_phone' => $listing->whatsapp_phone,
            'status' => $listing->status->value,
            'status_label' => $listing->status->label(),
            'published_at' => $listing->published_at?->toISOString(),
            'public_url' => $listing->isPublished() ? self::publicUrl($listing) : null,
            'media' => $listing->media
                ->map(fn (ListingMedia $media): array => self::ownerMedia($media, $listing, $teamSlug))
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function publicCard(Listing $listing, ?float $distanceKm = null): array
    {
        return [
            'id' => $listing->id,
            'name' => $listing->name,
            'slug' => $listing->slug,
            'city' => $listing->city,
            'state' => $listing->state,
            'capacity' => $listing->capacity,
            'services' => $listing->serviceLabels(),
            'cover_url' => $listing->coverImage?->url,
            'latitude' => $listing->latitude !== null ? (float) $listing->latitude : null,
            'longitude' => $listing->longitude !== null ? (float) $listing->longitude : null,
            'distance_km' => $distanceKm !== null ? round($distanceKm, 1) : null,
            'url' => self::publicUrl($listing),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function publicDetail(Listing $listing): array
    {
        return [
            'id' => $listing->id,
            'name' => $listing->name,
            'slug' => $listing->slug,
            'description' => $listing->description,
            'address' => $listing->address,
            'city' => $listing->city,
            'state' => $listing->state,
            'country' => $listing->country,
            'postal_code' => $listing->postal_code,
            'latitude' => $listing->latitude !== null ? (float) $listing->latitude : null,
            'longitude' => $listing->longitude !== null ? (float) $listing->longitude : null,
            'capacity' => $listing->capacity,
            'services' => collect($listing->services ?? [])
                ->map(fn (string $key): array => [
                    'key' => $key,
                    'label' => TerraceServiceCatalog::label($key),
                ])
                ->values()
                ->all(),
            'serviceGroups' => TerraceServiceCatalog::groupsFor($listing->services ?? []),
            'pricing' => ListingCommercialPresenter::pricing($listing),
            'packages' => $listing->packages
                ->where('active', true)
                ->map(fn (ListingPackage $package): array => ListingCommercialPresenter::publicPackage($package))
                ->values()
                ->all(),
            'commercialServices' => $listing->commercialServices
                ->where('active', true)
                ->map(fn (ListingService $service): array => ListingCommercialPresenter::publicService($service))
                ->values()
                ->all(),
            'promotions' => $listing->promotions
                ->filter(fn (ListingPromotion $promotion): bool => $promotion->isCurrentlyActive())
                ->map(fn (ListingPromotion $promotion): array => ListingCommercialPresenter::publicPromotion($promotion))
                ->values()
                ->all(),
            'details' => collect(TerraceDetailCatalog::keys())
                ->filter(fn (string $key): bool => filled(($listing->details ?? [])[$key] ?? null))
                ->map(fn (string $key): array => [
                    'key' => $key,
                    'label' => TerraceDetailCatalog::label($key),
                    'value' => (string) (($listing->details ?? [])[$key] ?? ''),
                ])
                ->values()
                ->all(),
            'rules' => $listing->rules,
            'contact_name' => $listing->contact_name,
            'contact_email' => $listing->contact_email,
            'contact_phone' => $listing->contact_phone,
            'whatsapp_url' => self::whatsappUrl($listing->whatsapp_phone),
            'account' => [
                'name' => $listing->team?->name,
                'logo_url' => $listing->team?->logoUrl(),
                'contact_email' => $listing->team?->contact_email,
                'contact_phone' => $listing->team?->contact_phone,
                'whatsapp_url' => WhatsApp::url(
                    $listing->team?->whatsapp_phone,
                    "Hola, me interesa {$listing->name}.",
                ),
            ],
            'timezone' => $listing->timezone,
            'url' => self::publicUrl($listing),
            'media' => $listing->media
                ->map(fn (ListingMedia $media): array => self::publicMedia($media))
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function ownerMedia(ListingMedia $media, Listing $listing, string $teamSlug): array
    {
        return [
            ...self::publicMedia($media),
            'delete_url' => route('listings.media.destroy', [
                'current_team' => $teamSlug,
                'listing' => $listing->slug,
                'media' => $media->id,
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function publicMedia(ListingMedia $media): array
    {
        return [
            'id' => $media->id,
            'type' => $media->type->value,
            'type_label' => $media->type->label(),
            'url' => $media->url,
            'mime_type' => $media->mime_type,
            'alt_text' => $media->alt_text,
            'sort_order' => $media->sort_order,
        ];
    }

    public static function publicUrl(Listing $listing): string
    {
        return route('public.listings.show', ['listing' => $listing->slug]);
    }

    public static function whatsappUrl(?string $phone): ?string
    {
        return WhatsApp::url($phone);
    }
}
