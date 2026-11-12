<?php

namespace Modules\Listings\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Listings\Models\Listing;
use Modules\Listings\Models\ListingEventType;
use Tests\TestCase;

class GuidedSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_guided_search_page_is_public_and_exposes_the_catalogs(): void
    {
        $this->get(route('public.guided-search.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listings/Public/GuidedSearch')
                ->has('locations.Jalisco')
                ->has('eventTypes', 13)
                ->has('featuredCharacteristics', 4)
                ->has('contact.guest_ranges', 4)
                ->has('urls.results'));

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('urls.guidedSearch', route('public.guided-search.index')));
    }

    public function test_guided_results_apply_features_events_capacity_and_geographic_coverage(): void
    {
        [, $team] = $this->ownerWithTeam();
        $weddings = ListingEventType::query()->where('slug', 'bodas')->firstOrFail();

        $match = Listing::factory()->forTeam($team)->published()->create([
            'name' => 'Coincidencia guiada',
            'city' => 'Guadalajara',
            'state' => 'Jalisco',
            'latitude' => 20.6750,
            'longitude' => -103.3450,
            'capacity' => 100,
            'services' => ['garden', 'pool', 'wifi'],
        ]);
        $match->eventTypes()->attach($weddings->id);

        $withoutPool = Listing::factory()->forTeam($team)->published()->create([
            'name' => 'Sin alberca',
            'city' => 'Guadalajara',
            'state' => 'Jalisco',
            'latitude' => 20.6760,
            'longitude' => -103.3460,
            'capacity' => 100,
            'services' => ['garden', 'wifi'],
        ]);
        $withoutPool->eventTypes()->attach($weddings->id);

        $outOfCoverage = Listing::factory()->forTeam($team)->published()->create([
            'name' => 'Fuera de cobertura',
            'city' => 'Cuauhtémoc',
            'state' => 'CDMX',
            'latitude' => 19.4326,
            'longitude' => -99.1332,
            'capacity' => 100,
            'services' => ['garden', 'pool', 'wifi'],
        ]);
        $outOfCoverage->eventTypes()->attach($weddings->id);

        $response = $this->getJson(route('public.guided-search.results', [
            'features' => ['garden', 'pool'],
            'event' => 'bodas',
            'capacity' => 80,
            'lat' => 20.6736,
            'lng' => -103.3440,
            'radius' => 10,
        ]));

        $response->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('listings.0.name', $match->name)
            ->assertJsonPath('listings.0.capacity', 100);

        $this->assertNotEmpty($response->json('listings.0.contact_url'));
    }

    public function test_radius_search_excludes_listings_without_coordinates_or_outside_the_radius(): void
    {
        [, $team] = $this->ownerWithTeam();

        $inside = Listing::factory()->forTeam($team)->published()->create([
            'name' => 'Dentro del radio',
            'latitude' => 20.6750,
            'longitude' => -103.3450,
            'services' => ['garden'],
        ]);
        Listing::factory()->forTeam($team)->published()->create([
            'name' => 'Fuera del radio',
            'latitude' => 21.0000,
            'longitude' => -103.7000,
            'services' => ['garden'],
        ]);
        Listing::factory()->forTeam($team)->published()->create([
            'name' => 'Sin coordenadas',
            'latitude' => null,
            'longitude' => null,
            'services' => ['garden'],
        ]);

        $this->getJson(route('public.guided-search.results', [
            'features' => ['garden'],
            'lat' => 20.6736,
            'lng' => -103.3440,
            'radius' => 5,
        ]))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('listings.0.name', $inside->name);
    }

    public function test_guided_results_are_paginated(): void
    {
        [, $team] = $this->ownerWithTeam();

        for ($index = 1; $index <= 13; $index++) {
            Listing::factory()->forTeam($team)->published()->create([
                'name' => "Terraza guiada {$index}",
                'city' => 'Guadalajara',
                'state' => 'Jalisco',
                'services' => ['garden'],
            ]);
        }

        $this->getJson(route('public.guided-search.results', [
            'features' => ['garden'],
            'page' => 2,
        ]))
            ->assertOk()
            ->assertJsonPath('total', 13)
            ->assertJsonPath('current_page', 2)
            ->assertJsonPath('last_page', 2)
            ->assertJsonCount(1, 'listings');
    }

    /**
     * @return array{0: User, 1: Team}
     */
    private function ownerWithTeam(): array
    {
        $owner = User::factory()->create();

        return [$owner, $owner->currentTeam];
    }
}
