<?php

namespace Modules\Listings\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Listings\Enums\ListingServicePriceUnit;
use Modules\Listings\Models\Listing;
use Modules\Listings\Models\ListingPackage;
use Modules\Listings\Models\ListingPriceRange;
use Modules\Listings\Models\ListingPromotion;
use Modules\Listings\Models\ListingService;
use Tests\TestCase;

class ListingCommercialTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_configure_prices_and_public_detail_shows_them(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();

        $this->actingAs($owner)
            ->patch(route('listings.pricing.update', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]), [
                'price_from' => '1900',
                'daily_pricing' => [
                    'monday' => [
                        'hours' => 10,
                        'entry_time' => '12:00',
                        'exit_time' => '20:00',
                        'price' => '5000',
                    ],
                    'friday' => [
                        'hours' => 10,
                        'entry_time' => '12:00',
                        'exit_time' => '20:00',
                        'price' => '6500',
                    ],
                ],
                'included_hours' => 8,
                'included_people' => 50,
                'extra_hour' => '400',
                'extra_person' => '50',
                'deposit_required' => '1000',
                'price_notes' => 'Precios por evento.',
            ])
            ->assertSessionHasNoErrors();

        $listing->refresh();

        $this->assertSame(190000, $listing->price_from_cents);
        $this->assertSame(500000, $listing->daily_pricing['monday']['price_cents']);
        $this->assertSame(650000, $listing->daily_pricing['friday']['price_cents']);
        $this->assertSame(40000, $listing->extra_hour_cents);
        $this->assertSame('MXN', $listing->currency);
        $this->assertSame(['wifi', 'bathroom'], $listing->services);

        $this->get(route('public.listings.show', ['listing' => $listing->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('listing.pricing.has_pricing', true)
                ->where('listing.pricing.price_from_formatted', '$1,900.00 MXN')
                ->where('listing.pricing.daily_prices.0.label', 'Lunes')
                ->where('listing.pricing.daily_prices.0.hours', 10)
                ->where('listing.pricing.daily_prices.0.entry_time_label', '12:00 pm')
                ->where('listing.pricing.daily_prices.0.exit_time_label', '8:00 pm')
                ->where('listing.pricing.daily_prices.0.formatted', '$5,000.00 MXN')
                ->where('listing.pricing.daily_prices.4.label', 'Viernes')
                ->where('listing.pricing.daily_prices.4.formatted', '$6,500.00 MXN')
                ->where('listing.pricing.included_hours', 8)
                ->where('listing.pricing.extra_hour_formatted', '$400.00 MXN')
                ->where('listing.pricing.price_notes', 'Precios por evento.'));
    }

    public function test_package_crud_is_scoped_and_only_active_packages_are_public(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();

        $this->actingAs($owner)
            ->post(route('listings.packages.store', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]), [
                'name' => 'Paquete Familiar',
                'description' => 'Ocho horas para la familia.',
                'price' => '5000',
                'duration_hours' => 8,
                'included_people' => 50,
                'included_services' => ['dj'],
                'conditions' => 'Anticipo no reembolsable.',
                'active' => true,
                'sort_order' => 2,
            ])
            ->assertSessionHasNoErrors();

        $package = ListingPackage::query()->firstOrFail();
        $this->assertSame(500000, $package->price_cents);
        $this->assertSame(['dj'], $package->included_services);
        $this->assertSame(2, $package->sort_order);

        $inactive = ListingPackage::factory()->forListing($listing)->create([
            'active' => false,
            'sort_order' => 3,
        ]);

        $this->get(route('public.listings.show', ['listing' => $listing->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('listing.packages', 1)
                ->where('listing.packages.0.id', $package->id)
                ->where('listing.packages.0.price_formatted', '$5,000.00 MXN')
                ->missing('listing.packages.0.amenities')
                ->where('listing.packages.0.services.0.label', 'DJ'));

        $this->actingAs($owner)
            ->patch(route('listings.packages.update', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
                'package' => $package->id,
            ]), [
                'name' => 'Paquete actualizado',
                'price' => '5200',
                'duration_hours' => 6,
                'included_people' => 40,
                'included_services' => [],
                'active' => false,
                'sort_order' => 1,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Paquete actualizado', $package->fresh()->name);
        $this->assertFalse($package->fresh()->active);
        $this->assertSame([], $package->fresh()->included_services);
        $this->assertNotNull($inactive);
    }

    public function test_package_validation_rejects_unknown_services(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();

        $this->actingAs($owner)
            ->post(route('listings.packages.store', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]), [
                'name' => 'Paquete inválido',
                'price' => '1000',
                'duration_hours' => 8,
                'included_people' => 20,
                'included_services' => ['inventada'],
                'active' => true,
            ])
            ->assertSessionHasErrors('included_services.0');
    }

    public function test_service_crud_is_unique_and_publicly_classified(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();

        $this->actingAs($owner)
            ->post(route('listings.services.store', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]), [
                'service_key' => 'dj',
                'description' => 'DJ para cuatro horas.',
                'pricing_mode' => 'additional',
                'provider' => 'external',
                'price' => '2500',
                'price_unit' => ListingServicePriceUnit::Event->value,
                'active' => true,
            ])
            ->assertSessionHasNoErrors();

        $service = ListingService::query()->firstOrFail();
        $this->assertSame(250000, $service->price_cents);
        $this->assertSame('DJ', $service->serviceLabel());
        $this->assertSame('additional', $service->pricing_mode->value);
        $this->assertSame('external', $service->provider->value);

        ListingService::factory()->forListing($listing)->create([
            'service_key' => 'waiters',
            'active' => false,
        ]);

        $this->actingAs($owner)
            ->post(route('listings.services.store', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]), [
                'service_key' => 'dj',
                'price_unit' => ListingServicePriceUnit::Event->value,
                'active' => true,
            ])
            ->assertSessionHasErrors('service_key');

        $this->get(route('public.listings.show', ['listing' => $listing->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('listing.commercialServices', 1)
                ->where('listing.commercialServices.0.label', 'DJ')
                ->where('listing.commercialServices.0.is_included', false)
                ->where('listing.commercialServices.0.pricing_mode', 'additional')
                ->where('listing.commercialServices.0.pricing_mode_label', 'Costo adicional')
                ->where('listing.commercialServices.0.provider', 'external')
                ->where('listing.commercialServices.0.provider_label', 'Proveedor externo')
                ->where('listing.commercialServices.0.has_price', true)
                ->where('listing.commercialServices.0.price_formatted', '$2,500.00 MXN')
                ->where('listing.commercialServices.0.price_unit_label', 'Por evento'));

        $this->actingAs($owner)
            ->patch(route('listings.services.update', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
                'service' => $service->id,
            ]), [
                'service_key' => 'dj',
                'pricing_mode' => 'included',
                'provider' => 'own',
                'price_unit' => ListingServicePriceUnit::Event->value,
                'active' => true,
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue($service->fresh()->is_included);
        $this->assertSame('included', $service->fresh()->pricing_mode->value);
        $this->assertSame('own', $service->fresh()->provider->value);
        $this->assertNull($service->fresh()->price_cents);
    }

    public function test_service_pricing_mode_controls_price_requirements(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();

        $this->actingAs($owner)
            ->post(route('listings.services.store', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]), [
                'service_key' => 'bartender',
                'pricing_mode' => 'additional',
                'provider' => 'own',
                'price_unit' => ListingServicePriceUnit::Event->value,
                'active' => true,
            ])
            ->assertSessionHasErrors('price');

        $this->actingAs($owner)
            ->post(route('listings.services.store', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]), [
                'service_key' => 'live_music',
                'pricing_mode' => 'quote',
                'provider' => 'external',
                'price_unit' => ListingServicePriceUnit::Event->value,
                'active' => true,
            ])
            ->assertSessionHasNoErrors();

        $quote = ListingService::query()->where('service_key', 'live_music')->firstOrFail();
        $this->assertSame('quote', $quote->pricing_mode->value);
        $this->assertNull($quote->price_cents);

        $this->actingAs($owner)
            ->post(route('listings.services.store', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]), [
                'service_key' => 'event_coordinator',
                'pricing_mode' => 'included',
                'provider' => 'own',
                'price' => '1200',
                'price_unit' => ListingServicePriceUnit::Event->value,
                'active' => true,
            ])
            ->assertSessionHasNoErrors();

        $included = ListingService::query()->where('service_key', 'event_coordinator')->firstOrFail();
        $this->assertSame('included', $included->pricing_mode->value);
        $this->assertNull($included->price_cents);
    }

    public function test_promotions_only_show_when_current_and_active(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();

        $this->actingAs($owner)
            ->post(route('listings.promotions.store', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]), [
                'title' => 'Promoción entre semana',
                'description' => 'Lunes a jueves.',
                'promotional_price' => '1600',
                'previous_price' => '1900',
                'starts_at' => now()->subDay()->toDateString(),
                'ends_at' => now()->addDays(10)->toDateString(),
                'applies_on' => ['mon', 'tue', 'wed', 'thu'],
                'conditions' => 'No válida en días festivos.',
                'active' => true,
            ])
            ->assertSessionHasNoErrors();

        $promotion = ListingPromotion::query()->firstOrFail();
        $this->assertSame(160000, $promotion->promotional_price_cents);
        $this->assertSame(['mon', 'tue', 'wed', 'thu'], $promotion->applies_on);

        ListingPromotion::factory()->forListing($listing)->create([
            'title' => 'Promoción futura',
            'starts_at' => now()->addDays(5)->toDateString(),
            'ends_at' => now()->addDays(15)->toDateString(),
        ]);
        ListingPromotion::factory()->forListing($listing)->create([
            'title' => 'Promoción vencida',
            'starts_at' => now()->subDays(15)->toDateString(),
            'ends_at' => now()->subDays(5)->toDateString(),
        ]);
        ListingPromotion::factory()->forListing($listing)->create([
            'title' => 'Promoción inactiva',
            'active' => false,
        ]);

        $this->get(route('public.listings.show', ['listing' => $listing->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('listing.promotions', 1)
                ->where('listing.promotions.0.id', $promotion->id)
                ->where('listing.promotions.0.promotional_price_formatted', '$1,600.00 MXN')
                ->where('listing.promotions.0.previous_price_formatted', '$1,900.00 MXN')
                ->where('listing.promotions.0.applies_on', ['Lunes', 'Martes', 'Miércoles', 'Jueves']));
    }

    public function test_promotion_dates_must_be_valid(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();

        $this->actingAs($owner)
            ->post(route('listings.promotions.store', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]), [
                'title' => 'Fechas inválidas',
                'starts_at' => now()->toDateString(),
                'ends_at' => now()->subDay()->toDateString(),
                'active' => true,
            ])
            ->assertSessionHasErrors('ends_at');
    }

    public function test_commercial_records_are_isolated_by_team(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $foreignListing = Listing::factory()->forTeam(Team::factory()->create())->create();
        $foreignPackage = ListingPackage::factory()->forListing($foreignListing)->create();
        $foreignService = ListingService::factory()->forListing($foreignListing)->create();
        $foreignPromotion = ListingPromotion::factory()->forListing($foreignListing)->create();

        $this->actingAs($owner)
            ->patch(route('listings.packages.update', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
                'package' => $foreignPackage->id,
            ]), [
                'name' => 'Intruso',
                'price' => '100',
                'duration_hours' => 1,
                'included_people' => 1,
                'active' => true,
            ])
            ->assertNotFound();

        $this->actingAs($owner)
            ->patch(route('listings.services.update', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
                'service' => $foreignService->id,
            ]), [
                'service_key' => 'dj',
                'price_unit' => ListingServicePriceUnit::Event->value,
                'active' => true,
            ])
            ->assertNotFound();

        $this->actingAs($owner)
            ->patch(route('listings.promotions.update', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
                'promotion' => $foreignPromotion->id,
            ]), [
                'title' => 'Intrusa',
                'starts_at' => now()->toDateString(),
                'ends_at' => now()->addDay()->toDateString(),
                'active' => true,
            ])
            ->assertNotFound();
    }

    public function test_public_detail_omits_empty_commercial_sections(): void
    {
        [, , $listing] = $this->ownerListing();

        $this->get(route('public.listings.show', ['listing' => $listing->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('listing.pricing.has_pricing', false)
                ->has('listing.priceRanges', 0)
                ->has('listing.packages', 0)
                ->has('listing.commercialServices', 0)
                ->has('listing.promotions', 0));
    }

    public function test_commercial_admin_pages_render_with_existing_listing(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        ListingPackage::factory()->forListing($listing)->create();
        ListingService::factory()->forListing($listing)->create();
        ListingPromotion::factory()->forListing($listing)->create();
        ListingPriceRange::factory()->forListing($listing)->create();

        $this->actingAs($owner)
            ->get(route('listings.pricing.edit', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listings/Pricing')
                ->where('listing.id', $listing->id)
                ->has('priceRanges', 1));

        $this->actingAs($owner)
            ->get(route('listings.packages.index', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listings/Packages/Index')
                ->has('packages', 1)
                ->missing('amenityGroups')
                ->has('serviceGroups'));

        $this->actingAs($owner)
            ->get(route('listings.services.index', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listings/Services/Index')
                ->has('services', 1)
                ->has('serviceGroups')
                ->has('priceUnits', 4)
                ->has('pricingModes', 3)
                ->has('providers', 2));

        $this->actingAs($owner)
            ->get(route('listings.promotions.index', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listings/Promotions/Index')
                ->has('promotions', 1)
                ->has('weekdays', 7));

        $this->actingAs($owner)
            ->get(route('listings.manage', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listings/Manage')
                ->where('commercial.packages', 1)
                ->where('commercial.services', 1)
                ->where('commercial.promotions', 1)
                ->where('urls.pricing', route('listings.pricing.edit', [
                    'current_team' => $team->slug,
                    'listing' => $listing->slug,
                ])));
    }

    public function test_price_ranges_crud_and_public_visibility(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();

        $this->actingAs($owner)
            ->post(route('listings.pricing.ranges.store', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]), [
                'name' => 'Temporada alta',
                'starts_at' => now()->subDay()->toDateString(),
                'ends_at' => now()->addDays(20)->toDateString(),
                'daily_pricing' => [
                    'monday' => [
                        'hours' => 10,
                        'entry_time' => '12:00',
                        'exit_time' => '20:00',
                        'price' => '3200',
                    ],
                    'friday' => [
                        'hours' => 10,
                        'entry_time' => '12:00',
                        'exit_time' => '22:00',
                        'price' => '4500',
                    ],
                ],
                'included_people' => 50,
                'extra_hour' => '450',
                'extra_person' => '60',
                'deposit_required' => '1500',
                'conditions' => 'No aplica en días festivos.',
                'active' => true,
            ])
            ->assertSessionHasNoErrors();

        $range = ListingPriceRange::query()->firstOrFail();
        $this->assertSame(320000, $range->price_cents);
        $this->assertSame(320000, $range->daily_pricing['monday']['price_cents']);
        $this->assertSame(450000, $range->daily_pricing['friday']['price_cents']);
        $this->assertSame(45000, $range->extra_hour_cents);

        ListingPriceRange::factory()->forListing($listing)->create([
            'name' => 'Temporada vencida',
            'starts_at' => now()->subDays(20)->toDateString(),
            'ends_at' => now()->subDays(10)->toDateString(),
        ]);
        ListingPriceRange::factory()->forListing($listing)->create([
            'name' => 'Temporada inactiva',
            'active' => false,
        ]);

        $this->get(route('public.listings.show', ['listing' => $listing->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('listing.priceRanges', 1)
                ->where('listing.priceRanges.0.id', $range->id)
                ->where('listing.priceRanges.0.name', 'Temporada alta')
                ->where('listing.priceRanges.0.price_formatted', '$3,200.00 MXN')
                ->where('listing.priceRanges.0.daily_pricing.0.label', 'Lunes')
                ->where('listing.priceRanges.0.daily_pricing.0.hours', 10)
                ->where('listing.priceRanges.0.daily_pricing.0.entry_time_label', '12:00 pm')
                ->where('listing.priceRanges.0.daily_pricing.0.exit_time_label', '8:00 pm')
                ->where('listing.priceRanges.0.daily_pricing.0.formatted', '$3,200.00 MXN')
                ->where('listing.priceRanges.0.daily_pricing.4.formatted', '$4,500.00 MXN')
                ->where('listing.priceRanges.0.status.value', 'current')
                ->where('listing.priceRanges.0.conditions', 'No aplica en días festivos.'));

        $this->actingAs($owner)
            ->patch(route('listings.pricing.ranges.update', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
                'range' => $range->id,
            ]), [
                'name' => 'Temporada alta actualizada',
                'starts_at' => now()->subDay()->toDateString(),
                'ends_at' => now()->addDays(20)->toDateString(),
                'daily_pricing' => [
                    'monday' => [
                        'hours' => 8,
                        'entry_time' => '13:00',
                        'exit_time' => '21:00',
                        'price' => '3400',
                    ],
                ],
                'active' => false,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Temporada alta actualizada', $range->fresh()->name);
        $this->assertFalse($range->fresh()->active);

        $range->refresh();
        $this->assertSame(340000, $range->price_cents);
        $this->assertSame(['monday'], array_keys($range->daily_pricing));
        $this->assertSame(340000, $range->daily_pricing['monday']['price_cents']);
        $this->assertSame('13:00', $range->daily_pricing['monday']['entry_time']);

        $this->actingAs($owner)
            ->delete(route('listings.pricing.ranges.destroy', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
                'range' => $range->id,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('listing_price_ranges', ['id' => $range->id]);
    }

    public function test_price_range_dates_and_team_are_validated(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();

        $this->actingAs($owner)
            ->post(route('listings.pricing.ranges.store', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]), [
                'name' => 'Fechas inválidas',
                'starts_at' => now()->toDateString(),
                'ends_at' => now()->subDay()->toDateString(),
                'price' => '1000',
                'active' => true,
            ])
            ->assertSessionHasErrors('ends_at');

        $this->actingAs($owner)
            ->post(route('listings.pricing.ranges.store', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]), [
                'name' => 'Horario inválido',
                'starts_at' => now()->toDateString(),
                'ends_at' => now()->addDay()->toDateString(),
                'daily_pricing' => [
                    'monday' => [
                        'hours' => 30,
                        'entry_time' => '25:00',
                        'exit_time' => '20:00',
                        'price' => '1000',
                    ],
                    'lunes' => [
                        'hours' => 8,
                        'price' => '1000',
                    ],
                ],
                'active' => true,
            ])
            ->assertSessionHasErrors([
                'daily_pricing',
                'daily_pricing.monday.hours',
                'daily_pricing.monday.entry_time',
            ]);

        $this->actingAs($owner)
            ->post(route('listings.pricing.ranges.store', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]), [
                'name' => 'Sin precio',
                'starts_at' => now()->toDateString(),
                'ends_at' => now()->addDay()->toDateString(),
                'active' => true,
            ])
            ->assertSessionHasErrors('daily_pricing');

        $foreignListing = Listing::factory()->forTeam(Team::factory()->create())->create();
        $foreignRange = ListingPriceRange::factory()->forListing($foreignListing)->create();

        $this->actingAs($owner)
            ->patch(route('listings.pricing.ranges.update', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
                'range' => $foreignRange->id,
            ]), [
                'name' => 'Intrusa',
                'starts_at' => now()->toDateString(),
                'ends_at' => now()->addDay()->toDateString(),
                'price' => '1000',
                'active' => true,
            ])
            ->assertNotFound();
    }

    public function test_price_range_without_daily_rows_falls_back_to_flat_price(): void
    {
        [, , $listing] = $this->ownerListing();

        ListingPriceRange::factory()->forListing($listing)->create([
            'name' => 'Temporada heredada',
            'price_cents' => 500000,
            'daily_pricing' => null,
            'included_hours' => 6,
        ]);

        $this->get(route('public.listings.show', ['listing' => $listing->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('listing.priceRanges', 1)
                ->where('listing.priceRanges.0.price_formatted', '$5,000.00 MXN')
                ->where('listing.priceRanges.0.daily_pricing.0.hours', 6)
                ->where('listing.priceRanges.0.daily_pricing.0.formatted', '$5,000.00 MXN')
                ->where('listing.priceRanges.0.daily_pricing.6.formatted', '$5,000.00 MXN'));
    }

    /**
     * @return array{0: User, 1: Team, 2: Listing}
     */
    private function ownerListing(): array
    {
        $owner = User::factory()->create();
        $team = $owner->currentTeam;
        $listing = Listing::factory()->forTeam($team)->published()->create();

        return [$owner, $team, $listing];
    }
}
