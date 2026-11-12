<?php

namespace Modules\Listings\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Listings\Models\Listing;
use Modules\Listings\Models\ListingEventType;
use Modules\Listings\Support\TerraceServiceCatalog;
use Tests\TestCase;

class ListingFeatureFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_general_features_use_and_logic(): void
    {
        [, $team] = $this->ownerWithTeam();

        $full = $this->publishedListing(
            $team,
            'Completa',
            ['wifi', 'parking', 'pool', 'covered_terrace'],
        );
        $partial = $this->publishedListing(
            $team,
            'Parcial',
            ['wifi', 'parking', 'pool'],
        );
        $this->publishedListing($team, 'Solo wifi', ['wifi']);

        $this->get(route('public.listings.index', [
            'features' => ['wifi', 'parking', 'pool', 'covered_terrace'],
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.name', $full->name)
                ->where('filters.features', [
                    'wifi',
                    'parking',
                    'pool',
                    'covered_terrace',
                ]));

        $this->get(route('public.listings.index', [
            'features' => ['wifi', 'parking'],
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 2)
                ->where('listings.data', fn ($data) => collect($data)
                    ->pluck('name')
                    ->sort()
                    ->values()
                    ->all() === collect([$full->name, $partial->name])
                    ->sort()
                    ->values()
                    ->all()));
    }

    public function test_client_can_select_ten_features_but_not_eleven(): void
    {
        $keys = array_values(array_keys(TerraceServiceCatalog::all()));
        $ten = array_slice($keys, 0, 10);
        $eleven = array_slice($keys, 0, 11);

        $this->get(route('public.listings.index', ['features' => $ten]))
            ->assertOk();

        $this->get(route('public.listings.index', ['features' => $eleven]))
            ->assertSessionHasErrors('features');
    }

    public function test_legacy_service_counts_towards_the_ten_feature_limit(): void
    {
        $keys = array_values(array_keys(TerraceServiceCatalog::all()));
        $ten = array_slice($keys, 0, 10);
        $extra = array_values(array_diff($keys, $ten))[0];

        $this->get(route('public.listings.index', [
            'features' => $ten,
            'service' => $extra,
        ]))->assertSessionHasErrors('features');
    }

    public function test_duplicate_features_are_rejected(): void
    {
        $this->get(route('public.listings.index', [
            'features' => ['wifi', 'wifi'],
        ]))->assertSessionHasErrors('features.1');
    }

    public function test_features_combine_with_location_event_capacity_and_price(): void
    {
        [, $team] = $this->ownerWithTeam();
        $weddings = ListingEventType::query()->where('slug', 'bodas')->firstOrFail();

        $match = $this->publishedListing($team, 'Coincide', ['wifi', 'parking']);
        $match->update([
            'capacity' => 100,
            'price_from_cents' => 500000,
            'state' => 'Jalisco',
            'city' => 'Guadalajara',
        ]);
        $match->eventTypes()->attach($weddings->id);

        $small = $this->publishedListing($team, 'Capacidad baja', ['wifi', 'parking']);
        $small->update(['capacity' => 30, 'price_from_cents' => 500000]);
        $small->eventTypes()->attach($weddings->id);

        $expensive = $this->publishedListing($team, 'Precio alto', ['wifi', 'parking']);
        $expensive->update(['capacity' => 100, 'price_from_cents' => 700000]);
        $expensive->eventTypes()->attach($weddings->id);

        $withoutEvent = $this->publishedListing($team, 'Sin evento', ['wifi', 'parking']);
        $withoutEvent->update(['capacity' => 100, 'price_from_cents' => 500000]);

        $this->get(route('public.listings.index', [
            'state' => 'Jalisco',
            'city' => 'Guadalajara',
            'capacity' => 50,
            'price_max' => 6000,
            'event' => 'bodas',
            'features' => ['wifi', 'parking'],
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.name', $match->name)
                ->where('filters.features', ['wifi', 'parking'])
                ->where('filters.event', 'bodas'));
    }

    /**
     * @return array{0: User, 1: Team}
     */
    private function ownerWithTeam(): array
    {
        $owner = User::factory()->create();

        return [$owner, $owner->currentTeam];
    }

    /**
     * @param  array<int, string>  $services
     */
    private function publishedListing(Team $team, string $name, array $services): Listing
    {
        return Listing::factory()->forTeam($team)->published()->create([
            'name' => $name,
            'services' => $services,
        ]);
    }
}
