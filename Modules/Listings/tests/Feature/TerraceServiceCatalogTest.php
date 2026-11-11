<?php

namespace Modules\Listings\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Database\Seeders\DemoUsersWithListingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Listings\Models\Listing;
use Modules\Listings\Support\TerraceServiceCatalog;
use Tests\TestCase;

class TerraceServiceCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_contains_the_mvp_features_without_duplicates(): void
    {
        $all = TerraceServiceCatalog::all();

        $this->assertCount(43, $all);
        $this->assertSame(count($all), count(array_unique($all)));

        foreach ([
            'wifi',
            'bathroom',
            'parking',
            'accessible',
            'covered_terrace',
            'green_areas',
            'garden',
            'bar',
            'dining_room',
            'living_room',
            'rooftop',
            'pergola',
            'pool',
            'heated_pool',
            'paddling_pool',
            'jacuzzi',
            'lounge_chairs',
            'showers',
            'kitchen',
            'equipped_kitchen',
            'grill',
            'refrigerator',
            'stove',
            'griddle',
            'cooler',
            'tables',
            'kids_furniture',
            'table_linen',
            'sound',
            'lighting',
            'smart_tv',
            'heating',
            'air_conditioning',
            'bounce_house',
            'foosball',
            'soccer_field',
            'pinata_area',
            'playground',
            'frog_game',
            'fire_pit',
            'lodging',
            'rooms',
            'security_cameras',
        ] as $key) {
            $this->assertArrayHasKey($key, $all);
        }
    }

    public function test_catalog_groups_are_complete_and_non_empty(): void
    {
        $groups = TerraceServiceCatalog::groups();
        $all = TerraceServiceCatalog::all();

        $this->assertCount(7, $groups);
        $this->assertSame('Instalaciones y espacios', $groups[0]['label']);
        $this->assertSame('Seguridad', $groups[6]['label']);

        $grouped = [];

        foreach ($groups as $group) {
            $this->assertNotSame([], $group['services']);

            foreach ($group['services'] as $service) {
                $grouped[] = $service['value'];
            }
        }

        $this->assertSame(array_keys($all), $grouped);
        $this->assertSame($grouped, array_values(array_unique($grouped)));
    }

    public function test_priority_and_additional_filters_do_not_overlap(): void
    {
        $priority = array_column(TerraceServiceCatalog::priorityOptions(), 'value');
        $additional = array_column(TerraceServiceCatalog::additionalOptions(), 'value');

        $this->assertSame([
            'pool',
            'heated_pool',
            'parking',
            'wifi',
            'grill',
            'kitchen',
            'bathroom',
            'tables',
            'bounce_house',
            'green_areas',
            'covered_terrace',
            'lodging',
            'accessible',
        ], $priority);

        $this->assertSame([], array_values(array_intersect($priority, $additional)));
        $this->assertCount(43, array_merge($priority, $additional));
    }

    public function test_listing_form_receives_grouped_service_options(): void
    {
        $owner = User::factory()->create();
        $team = $owner->currentTeam;

        $this->actingAs($owner)
            ->get(route('listings.create', ['current_team' => $team->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listings/Create')
                ->has('serviceGroups', 7)
                ->where('serviceGroups.0.key', 'spaces')
                ->where('serviceGroups.0.label', 'Instalaciones y espacios')
                ->where('serviceGroups.4.key', 'entertainment')
                ->has('serviceGroups.4.services', 7)
                ->where('serviceGroups.4.services.0.value', 'bounce_house'));
    }

    public function test_listing_update_accepts_new_features_and_removes_duplicates(): void
    {
        $owner = User::factory()->create();
        $team = $owner->currentTeam;
        $listing = Listing::factory()->forTeam($team)->create([
            'services' => ['wifi'],
        ]);

        $this->actingAs($owner)
            ->patch(route('listings.update', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]), [
                'name' => $listing->name,
                'services' => ['wifi', 'bounce_house', 'pool', 'wifi'],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            ['wifi', 'bounce_house', 'pool'],
            $listing->fresh()->services,
        );
    }

    public function test_public_detail_groups_only_the_selected_features(): void
    {
        $team = Team::factory()->create();
        $listing = Listing::factory()->forTeam($team)->published()->create([
            'services' => ['wifi', 'pool', 'bounce_house'],
        ]);

        $this->get(route('public.listings.show', ['listing' => $listing->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('listing.services', 3)
                ->has('listing.serviceGroups', 3)
                ->where('listing.serviceGroups.0.key', 'spaces')
                ->where('listing.serviceGroups.0.services.0.value', 'wifi')
                ->where('listing.serviceGroups.1.key', 'pools')
                ->where('listing.serviceGroups.1.services.0.value', 'pool')
                ->where('listing.serviceGroups.2.key', 'entertainment')
                ->where('listing.serviceGroups.2.services.0.value', 'bounce_house'));
    }

    public function test_public_catalog_filters_by_a_new_feature(): void
    {
        $team = Team::factory()->create();
        $match = Listing::factory()->forTeam($team)->published()->create([
            'name' => 'Terraza con brincolín',
            'services' => ['wifi', 'bounce_house'],
        ]);
        Listing::factory()->forTeam($team)->published()->create([
            'name' => 'Terraza sin brincolín',
            'services' => ['wifi'],
        ]);

        $this->get(route('public.listings.index', ['service' => 'bounce_house']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.name', $match->name));
    }

    public function test_demo_seeder_is_idempotent_and_preserves_listing_features(): void
    {
        Storage::fake('public');
        Http::fake([
            '*' => Http::response(
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl2n0kAAAAASUVORK5CYII='),
                200,
                ['Content-Type' => 'image/png'],
            ),
        ]);

        $this->seed(DemoUsersWithListingsSeeder::class);

        $listingCount = Listing::query()->count();
        $services = Listing::query()->orderBy('id')->firstOrFail()->services;

        $this->seed(DemoUsersWithListingsSeeder::class);

        $this->assertSame($listingCount, Listing::query()->count());
        $this->assertSame(
            $services,
            Listing::query()->orderBy('id')->firstOrFail()->services,
        );
    }
}
