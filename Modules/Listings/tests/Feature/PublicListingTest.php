<?php

namespace Modules\Listings\Tests\Feature;

use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
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

    public function test_search_requires_latitude_and_longitude_together(): void
    {
        $this->get(route('public.listings.index', ['lat' => 19.4326]))
            ->assertSessionHasErrors('lat');
    }
}
