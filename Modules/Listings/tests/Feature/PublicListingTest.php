<?php

namespace Modules\Listings\Tests\Feature;

use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Bookings\Enums\AvailabilityStatus;
use Modules\Bookings\Models\ListingUnavailability;
use Modules\Listings\Models\Listing;
use Tests\TestCase;

class PublicListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_catalog_only_returns_published_listings(): void
    {
        $team = Team::factory()->create();
        $published = Listing::factory()->forTeam($team)->published()->create(['name' => 'Terraza Publicada']);
        Listing::factory()->forTeam($team)->create(['name' => 'Terraza Borrador']);
        Listing::factory()->forTeam($team)->suspended()->create(['name' => 'Terraza Suspendida']);

        $this->get(route('public.listings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listings/Public/Index')
                ->has('listings.data', 1)
                ->where('listings.data.0.name', $published->name)
                ->where('listings.data.0.url', route('public.listings.show', ['listing' => $published->slug])));
    }

    public function test_radius_search_returns_nearby_listings_and_excludes_far_ones(): void
    {
        $team = Team::factory()->create();
        $near = Listing::factory()->forTeam($team)->published()->withCoordinates(19.4400, -99.1400)->create(['name' => 'Terraza Cerca']);
        Listing::factory()->forTeam($team)->published()->withCoordinates(20.0000, -99.0000)->create(['name' => 'Terraza Lejos']);

        $this->get(route('public.listings.index', [
            'lat' => 19.4326,
            'lng' => -99.1332,
            'radius' => 10,
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listings/Public/Index')
                ->has('listings.data', 1)
                ->where('listings.data.0.name', $near->name));
    }

    public function test_public_detail_is_available_for_published_listing_only(): void
    {
        $team = Team::factory()->create();
        $published = Listing::factory()->forTeam($team)->published()->create();
        $draft = Listing::factory()->forTeam($team)->create();

        $this->get(route('public.listings.show', ['listing' => $published->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listings/Public/Show')
                ->where('listing.name', $published->name));

        $this->get(route('public.listings.show', ['listing' => $draft->slug]))
            ->assertNotFound();
    }

    public function test_public_detail_exposes_next_unavailable_dates(): void
    {
        $team = Team::factory()->create();
        $listing = Listing::factory()->forTeam($team)->published()->create();
        $date = now($listing->timezone)->addDays(3)->toDateString();

        ListingUnavailability::factory()->forListing($listing)->create([
            'date' => $date,
            'status' => AvailabilityStatus::Blocked,
        ]);

        $this->get(route('public.listings.show', ['listing' => $listing->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listings/Public/Show')
                ->has('unavailableDates', 1)
                ->where('unavailableDates.0.date', $date)
                ->where('unavailableDates.0.status', AvailabilityStatus::Blocked->value));
    }

    public function test_public_catalog_filters_by_state_and_municipality(): void
    {
        $team = Team::factory()->create();
        $match = Listing::factory()->forTeam($team)->published()->create([
            'name' => 'Terraza Cuauhtémoc',
            'state' => 'CDMX',
            'city' => 'Cuauhtémoc',
        ]);
        Listing::factory()->forTeam($team)->published()->create([
            'name' => 'Terraza Coyoacán',
            'state' => 'CDMX',
            'city' => 'Coyoacán',
        ]);
        Listing::factory()->forTeam($team)->published()->create([
            'name' => 'Terraza Guadalajara',
            'state' => 'Jalisco',
            'city' => 'Guadalajara',
        ]);

        $this->get(route('public.listings.index', [
            'state' => 'CDMX',
            'city' => 'Cuauhtémoc',
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.name', $match->name));
    }

    public function test_public_catalog_rejects_a_municipality_from_another_state(): void
    {
        $this->get(route('public.listings.index', [
            'state' => 'CDMX',
            'city' => 'Guadalajara',
        ]))
            ->assertSessionHasErrors('city');
    }

    public function test_public_catalog_filters_by_service(): void
    {
        $team = Team::factory()->create();
        $withPool = Listing::factory()->forTeam($team)->published()->create([
            'name' => 'Terraza con alberca',
            'services' => ['wifi', 'pool'],
        ]);
        Listing::factory()->forTeam($team)->published()->create([
            'name' => 'Terraza sin alberca',
            'services' => ['wifi'],
        ]);

        $this->get(route('public.listings.index', ['service' => 'pool']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listings/Public/Index')
                ->has('listings.data', 1)
                ->where('listings.data.0.name', $withPool->name));
    }

    public function test_location_query_resolves_a_municipality(): void
    {
        $team = Team::factory()->create();
        $match = Listing::factory()->forTeam($team)->published()->create([
            'name' => 'Terraza Guadalajara',
            'state' => 'Jalisco',
            'city' => 'Guadalajara',
        ]);
        Listing::factory()->forTeam($team)->published()->create([
            'name' => 'Terraza CDMX',
            'state' => 'CDMX',
            'city' => 'Cuauhtémoc',
        ]);

        $this->get(route('public.listings.index', ['q' => 'Guadalajara']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.name', $match->name)
                ->where('filters.state', 'Jalisco')
                ->where('filters.city', 'Guadalajara'));
    }

    public function test_location_query_resolves_a_postal_code(): void
    {
        $team = Team::factory()->create();
        $match = Listing::factory()->forTeam($team)->published()->create([
            'name' => 'Terraza con CP',
            'postal_code' => '44100',
            'state' => 'Jalisco',
            'city' => 'Guadalajara',
        ]);
        Listing::factory()->forTeam($team)->published()->create([
            'name' => 'Terraza sin CP',
            'postal_code' => '06000',
        ]);

        $this->get(route('public.listings.index', ['q' => '44100']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.name', $match->name)
                ->where('filters.postal_code', '44100'));
    }

    public function test_unknown_free_text_location_is_rejected(): void
    {
        $this->get(route('public.listings.index', ['q' => 'Colonia inventada']))
            ->assertSessionHasErrors('location');
    }

    public function test_bounds_search_only_returns_listings_inside_the_viewport(): void
    {
        $team = Team::factory()->create();
        $inside = Listing::factory()->forTeam($team)->published()
            ->withCoordinates(19.4326, -99.1332)
            ->create(['name' => 'Dentro del mapa']);
        Listing::factory()->forTeam($team)->published()
            ->withCoordinates(20.6597, -103.3496)
            ->create(['name' => 'Fuera del mapa']);

        $this->get(route('public.listings.index', [
            'north' => 19.6,
            'south' => 19.2,
            'east' => -98.9,
            'west' => -99.4,
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.name', $inside->name)
                ->has('mapListings', 1)
                ->where('bounds.north', 19.6));
    }

    public function test_price_filter_uses_the_published_starting_price(): void
    {
        $team = Team::factory()->create();
        Listing::factory()->forTeam($team)->published()->create([
            'name' => 'Económica',
            'price_from_cents' => 200000,
        ]);
        Listing::factory()->forTeam($team)->published()->create([
            'name' => 'Premium',
            'price_from_cents' => 800000,
        ]);

        $this->get(route('public.listings.index', ['price_max' => 3000]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.name', 'Económica'));
    }

    public function test_map_points_exclude_unpublished_and_listings_without_coordinates(): void
    {
        $team = Team::factory()->create();
        Listing::factory()->forTeam($team)->published()->withCoordinates(19.43, -99.13)->create();
        Listing::factory()->forTeam($team)->published()->create([
            'latitude' => null,
            'longitude' => null,
        ]);
        Listing::factory()->forTeam($team)->withCoordinates(19.43, -99.13)->create();

        $this->get(route('public.listings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('mapListings', 1)
                ->where('mapListings.0.latitude', 19.43));
    }

    public function test_location_suggestions_return_real_cities_and_postal_codes(): void
    {
        $team = Team::factory()->create();
        Listing::factory()->forTeam($team)->published()->create([
            'postal_code' => '44100',
            'state' => 'Jalisco',
            'city' => 'Guadalajara',
        ]);
        Listing::factory()->forTeam($team)->create([
            'postal_code' => '99999',
        ]);

        $this->getJson(route('public.listings.locations', ['q' => 'Guada']))
            ->assertOk()
            ->assertJsonPath('suggestions.0.type', 'city')
            ->assertJsonPath('suggestions.0.label', 'Guadalajara');

        $this->getJson(route('public.listings.locations', ['q' => '441']))
            ->assertOk()
            ->assertJsonFragment(['label' => '44100', 'type' => 'postal_code']);

        $this->getJson(route('public.listings.locations', ['q' => '999']))
            ->assertOk()
            ->assertJsonMissing(['label' => '99999']);
    }

    public function test_bounds_require_all_four_limits(): void
    {
        $this->get(route('public.listings.index', ['north' => 19.6]))
            ->assertSessionHasErrors('north');
    }

    public function test_price_range_must_be_ordered(): void
    {
        $this->get(route('public.listings.index', [
            'price_min' => 8000,
            'price_max' => 3000,
        ]))->assertSessionHasErrors('price_max');
    }

    public function test_search_requires_latitude_and_longitude_together(): void
    {
        $this->get(route('public.listings.index', ['lat' => 19.4326]))
            ->assertSessionHasErrors('lat');
    }

    public function test_home_page_features_only_publicly_visible_listings(): void
    {
        $team = Team::factory()->create();
        $published = Listing::factory()->forTeam($team)->published()->create([
            'name' => 'Terraza Portada',
            'city' => 'Cuauhtémoc',
            'state' => 'CDMX',
        ]);
        Listing::factory()->forTeam($team)->create(['name' => 'Terraza Borrador']);
        Listing::factory()->forTeam($team)->suspended()->create(['name' => 'Terraza Suspendida']);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listings/Public/Home')
                ->has('listings', 1)
                ->where('listings.0.name', $published->name)
                ->where('listings.0.url', route('public.listings.show', ['listing' => $published->slug]))
                ->where('totalListings', 1)
                ->has('cities', 1)
                ->where('cities.0.state', 'CDMX')
                ->where('cities.0.city', 'Cuauhtémoc')
                ->where('cities.0.count', 1)
                ->has('locations')
                ->has('services'));
    }

    public function test_catalog_card_exposes_the_starting_price_when_configured(): void
    {
        $team = Team::factory()->create();
        $listing = Listing::factory()->forTeam($team)->published()->create([
            'price_from_cents' => 350000,
            'currency' => 'MXN',
        ]);

        $this->get(route('public.listings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listings/Public/Index')
                ->where('listings.data.0.id', $listing->id)
                ->where('listings.data.0.price_from_formatted', '$3,500.00 MXN'));
    }
}
