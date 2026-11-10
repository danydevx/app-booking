<?php

namespace Modules\Listings\Support;

use App\Models\Team;
use Modules\Listings\Models\Listing;
use Modules\Listings\Models\ListingPackage;
use Modules\Listings\Models\ListingPromotion;
use Modules\Listings\Models\ListingService;

final class ListingCommercialPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function pricing(Listing $listing): array
    {
        $currency = $listing->currency ?: 'MXN';
        $hasPricing = $listing->price_from_cents !== null
            || $listing->price_weekday_cents !== null
            || $listing->price_weekend_cents !== null
            || $listing->included_hours !== null
            || $listing->included_people !== null
            || $listing->extra_hour_cents !== null
            || $listing->extra_person_cents !== null
            || $listing->deposit_required_cents !== null
            || filled($listing->price_notes);

        return [
            'has_pricing' => $hasPricing,
            'currency' => $currency,
            'price_from_cents' => $listing->price_from_cents,
            'price_weekday_cents' => $listing->price_weekday_cents,
            'price_weekend_cents' => $listing->price_weekend_cents,
            'price_from_formatted' => self::money($listing->price_from_cents, $currency),
            'price_weekday_formatted' => self::money($listing->price_weekday_cents, $currency),
            'price_weekend_formatted' => self::money($listing->price_weekend_cents, $currency),
            'included_hours' => $listing->included_hours,
            'included_people' => $listing->included_people,
            'extra_hour_formatted' => self::money($listing->extra_hour_cents, $currency),
            'extra_person_formatted' => self::money($listing->extra_person_cents, $currency),
            'deposit_required_formatted' => self::money($listing->deposit_required_cents, $currency),
            'price_notes' => $listing->price_notes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function publicPackage(ListingPackage $package): array
    {
        return [
            'id' => $package->id,
            'name' => $package->name,
            'description' => $package->description,
            'price_formatted' => self::money($package->price_cents, $package->listing->currency ?: 'MXN'),
            'currency' => $package->listing->currency ?: 'MXN',
            'duration_hours' => $package->duration_hours,
            'included_people' => $package->included_people,
            'amenities' => $package->amenityItems(),
            'services' => $package->serviceItems(),
            'conditions' => $package->conditions,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function ownerPackage(ListingPackage $package, Team $team, Listing $listing): array
    {
        return [
            ...self::publicPackage($package),
            'price' => self::decimal($package->price_cents),
            'included_amenity_keys' => $package->included_amenities ?? [],
            'included_service_keys' => $package->included_services ?? [],
            'active' => $package->active,
            'sort_order' => $package->sort_order,
            'update_url' => route('listings.packages.update', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
                'package' => $package->id,
            ]),
            'delete_url' => route('listings.packages.destroy', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
                'package' => $package->id,
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function publicService(ListingService $service): array
    {
        $currency = $service->listing->currency ?: 'MXN';

        return [
            'id' => $service->id,
            'service_key' => $service->service_key,
            'label' => $service->serviceLabel(),
            'description' => $service->description,
            'is_included' => $service->is_included,
            'price_formatted' => self::money($service->price_cents, $currency),
            'has_price' => $service->price_cents !== null,
            'price_unit' => $service->price_unit->value,
            'price_unit_label' => $service->price_unit->label(),
            'currency' => $currency,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function ownerService(ListingService $service, Team $team, Listing $listing): array
    {
        return [
            ...self::publicService($service),
            'price' => self::decimal($service->price_cents),
            'description' => $service->description,
            'active' => $service->active,
            'update_url' => route('listings.services.update', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
                'service' => $service->id,
            ]),
            'delete_url' => route('listings.services.destroy', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
                'service' => $service->id,
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function publicPromotion(ListingPromotion $promotion): array
    {
        $currency = $promotion->listing->currency ?: 'MXN';

        return [
            'id' => $promotion->id,
            'title' => $promotion->title,
            'description' => $promotion->description,
            'promotional_price_formatted' => self::money($promotion->promotional_price_cents, $currency),
            'previous_price_formatted' => self::money($promotion->previous_price_cents, $currency),
            'currency' => $currency,
            'starts_at' => $promotion->starts_at->toDateString(),
            'ends_at' => $promotion->ends_at->toDateString(),
            'starts_at_label' => $promotion->starts_at->format('d/m/Y'),
            'ends_at_label' => $promotion->ends_at->format('d/m/Y'),
            'applies_on' => $promotion->appliesOnLabels(),
            'conditions' => $promotion->conditions,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function ownerPromotion(ListingPromotion $promotion, Team $team, Listing $listing): array
    {
        return [
            ...self::publicPromotion($promotion),
            'promotional_price' => self::decimal($promotion->promotional_price_cents),
            'previous_price' => self::decimal($promotion->previous_price_cents),
            'applies_on_keys' => $promotion->applies_on ?? [],
            'active' => $promotion->active,
            'status' => self::promotionStatus($promotion),
            'update_url' => route('listings.promotions.update', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
                'promotion' => $promotion->id,
            ]),
            'delete_url' => route('listings.promotions.destroy', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
                'promotion' => $promotion->id,
            ]),
        ];
    }

    /**
     * @return array{value: string, label: string}
     */
    public static function promotionStatus(ListingPromotion $promotion): array
    {
        $today = now()->toDateString();

        if (! $promotion->active) {
            return ['value' => 'inactive', 'label' => 'Inactiva'];
        }

        if ($promotion->starts_at->toDateString() > $today) {
            return ['value' => 'future', 'label' => 'Futura'];
        }

        if ($promotion->ends_at->toDateString() < $today) {
            return ['value' => 'expired', 'label' => 'Vencida'];
        }

        return ['value' => 'current', 'label' => 'Vigente'];
    }

    public static function money(?int $cents, string $currency = 'MXN'): ?string
    {
        if ($cents === null) {
            return null;
        }

        return '$'.number_format($cents / 100, 2).' '.$currency;
    }

    public static function decimal(?int $cents): ?string
    {
        return $cents === null ? null : number_format($cents / 100, 2, '.', '');
    }
}
