<?php

namespace Modules\Bookings\Support;

use App\Models\Team;
use Modules\Bookings\Models\Customer;
use Modules\Listings\Models\Listing;
use Modules\Listings\Models\ListingPackage;
use Modules\Listings\Models\ListingService;

final class QuotationBuilderData
{
    private const UNIT_LABELS = [
        'hour' => 'Hora',
        'day' => 'Día',
        'night' => 'Noche',
        'event' => 'Evento',
        'service' => 'Servicio',
        'piece' => 'Pieza',
        'person' => 'Persona',
    ];

    /**
     * @return array<string, mixed>
     */
    public function forTeam(Team $team): array
    {
        $listings = Listing::query()
            ->with([
                'commercialServices' => fn ($query) => $query->where('active', true),
                'packages' => fn ($query) => $query->where('active', true),
                'eventTypes' => fn ($query) => $query->where('is_active', true),
            ])
            ->where('team_id', $team->id)
            ->orderBy('name')
            ->get();

        return [
            'team' => [
                'name' => $team->name,
                'slug' => $team->slug,
            ],
            'defaults' => [
                'currency' => (string) config('bookings.quotes.currency', 'MXN'),
                'tax_rate_bps' => (int) config('bookings.quotes.tax_rate_bps', 1600),
                'valid_days' => (int) config('bookings.quotes.valid_days', 15),
                'rental_mode' => 'fixed',
                'tax_mode' => 'none',
                'discount_type' => 'none',
            ],
            'listings' => $listings
                ->map(fn (Listing $listing): array => $this->listing($listing))
                ->values()
                ->all(),
            'customers' => Customer::query()
                ->where('team_id', $team->id)
                ->orderBy('name')
                ->limit(500)
                ->get(['id', 'name', 'email', 'phone'])
                ->map(fn (Customer $customer): array => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function listing(Listing $listing): array
    {
        return [
            'id' => $listing->id,
            'name' => $listing->name,
            'slug' => $listing->slug,
            'logo_url' => $listing->logoUrl(),
            'address' => $listing->address,
            'city' => $listing->city,
            'state' => $listing->state,
            'contact_email' => $listing->contact_email,
            'contact_phone' => $listing->contact_phone,
            'timezone' => $listing->timezone,
            'price_from_cents' => $listing->price_from_cents,
            'event_types' => $listing->eventTypes
                ->pluck('name')
                ->values()
                ->all(),
            'services' => $listing->commercialServices
                ->map(fn (ListingService $service): array => [
                    'id' => $service->id,
                    'key' => $service->service_key,
                    'label' => $service->serviceLabel(),
                    'description' => $service->description,
                    'price_cents' => (int) ($service->price_cents ?? 0),
                    'price_unit' => $service->price_unit->value,
                    'unit_label' => self::UNIT_LABELS[$service->price_unit->value],
                    'is_included' => $service->is_included,
                    'pricing_mode' => $service->pricing_mode->value,
                ])
                ->values()
                ->all(),
            'packages' => $listing->packages
                ->map(fn (ListingPackage $package): array => [
                    'id' => $package->id,
                    'name' => $package->name,
                    'description' => $package->description,
                    'price_cents' => $package->price_cents,
                    'duration_hours' => $package->duration_hours,
                    'included_people' => $package->included_people,
                    'conditions' => $package->conditions,
                    'services' => $package->serviceItems(),
                ])
                ->values()
                ->all(),
        ];
    }
}
