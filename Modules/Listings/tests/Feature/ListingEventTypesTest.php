<?php

namespace Modules\Listings\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Listings\Models\Listing;
use Modules\Listings\Models\ListingCharacteristic;
use Modules\Listings\Models\ListingEventType;
use Tests\TestCase;

class ListingEventTypesTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_and_update_multiple_event_types(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $weddings = $this->eventType('bodas');
        $birthdays = $this->eventType('cumpleanos');

        $this->actingAs($owner)
            ->post($this->storeUrl($team), $this->payload([
                'services' => ['garden', 'pool', 'wifi'],
                'event_types' => [$weddings->id, $birthdays->id],
            ]))
            ->assertRedirect();

        $listing = Listing::query()->where('team_id', $team->id)->firstOrFail();

        $this->assertSame(
            ['bodas', 'cumpleanos'],
            $listing->eventTypes->pluck('slug')->all(),
        );
        $this->assertSame(['garden', 'pool', 'wifi'], $listing->services);

        $this->actingAs($owner)
            ->patch($this->updateUrl($team, $listing), $this->payload([
                'services' => ['garden', 'covered_terrace'],
                'event_types' => [$weddings->id],
            ]))
            ->assertRedirect();

        $listing->refresh()->load('eventTypes');

        $this->assertSame(['bodas'], $listing->eventTypes->pluck('slug')->all());
        $this->assertSame(['garden', 'covered_terrace'], $listing->services);
    }

    public function test_listing_without_declared_event_types_is_visible_without_an_event_filter(): void
    {
        [, $team] = $this->ownerWithTeam();
        $listing = $this->publishedListing($team, 'Sin eventos', ['garden']);

        $this->get(route('public.listings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.name', $listing->name));

        $this->get(route('public.listings.index', ['event' => 'bodas']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('listings.data', 0));
    }

    public function test_featured_characteristics_use_and_logic_and_event_types_use_or_logic(): void
    {
        [, $team] = $this->ownerWithTeam();
        $weddings = $this->eventType('bodas');
        $birthdays = $this->eventType('cumpleanos');

        $allThree = $this->publishedListing(
            $team,
            'Jardín Alberca Techada',
            ['garden', 'pool', 'covered_terrace'],
            [$weddings, $birthdays],
        );
        $two = $this->publishedListing(
            $team,
            'Solo Jardín y Alberca',
            ['garden', 'pool'],
            [$weddings],
        );
        $third = $this->publishedListing(
            $team,
            'Jardín Techada Cumpleaños',
            ['garden', 'pool', 'covered_terrace'],
            [$birthdays],
        );
        $withoutEvents = $this->publishedListing(
            $team,
            'Techada sin eventos',
            ['garden', 'pool', 'covered_terrace'],
        );
        $this->publishedListing(
            $team,
            'Fuera de cobertura',
            ['garden', 'pool', 'covered_terrace'],
            [$weddings],
            'CDMX',
            'Cuauhtémoc',
        );

        $this->get(route('public.listings.index', [
            'features' => ['garden', 'pool'],
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('listings.data', 4));

        $this->get(route('public.listings.index', [
            'features' => ['garden', 'covered_terrace'],
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 3)
                ->where('filters.features', ['garden', 'covered_terrace'])
                ->where('listings.data', fn ($data) => collect($data)
                    ->pluck('name')
                    ->sort()
                    ->values()
                    ->all() === collect([
                        $allThree->name,
                        $third->name,
                        $withoutEvents->name,
                    ])->sort()->values()->all()));

        $this->get(route('public.listings.index', ['event' => 'bodas']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 2)
                ->where('listings.data', fn ($data) => collect($data)
                    ->pluck('name')
                    ->sort()
                    ->values()
                    ->all() === collect([
                        $allThree->name,
                        $two->name,
                    ])->sort()->values()->all()));

        $this->get(route('public.listings.index', [
            'event' => 'bodas',
            'features' => ['garden', 'covered_terrace'],
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.name', $allThree->name)
                ->where('filters.event', 'bodas')
                ->where('filters.features', ['garden', 'covered_terrace']));

        $this->get(route('public.listings.index', [
            'events' => ['bodas', 'cumpleanos'],
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('listings.data', 3));
    }

    public function test_filters_are_preserved_through_pagination(): void
    {
        [, $team] = $this->ownerWithTeam();
        $weddings = $this->eventType('bodas');

        for ($index = 1; $index <= 13; $index++) {
            $this->publishedListing(
                $team,
                "Terraza {$index}",
                ['garden'],
                [$weddings],
            );
        }

        $query = [
            'event' => 'bodas',
            'features' => ['garden'],
        ];

        $this->get(route('public.listings.index', $query))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 12)
                ->where('listings.total', 13)
                ->where('listings.current_page', 1)
                ->where('filters.event', 'bodas')
                ->where('filters.features', ['garden']));

        $this->get(route('public.listings.index', [
            ...$query,
            'page' => 2,
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.total', 13)
                ->where('listings.current_page', 2)
                ->where('filters.event', 'bodas')
                ->where('filters.features', ['garden']));
    }

    public function test_inactive_event_types_cannot_be_used_and_unfeatured_characteristics_remain_available(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $weddings = $this->eventType('bodas');
        $weddings->update(['is_active' => false]);

        ListingCharacteristic::query()
            ->where('service_key', 'green_areas')
            ->update(['is_featured' => false]);

        $this->get(route('public.listings.index', ['event' => 'bodas']))
            ->assertSessionHasErrors('event');

        $this->get(route('public.listings.index', [
            'features' => ['green_areas'],
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 0)
                ->where('filters.features', ['green_areas']));

        $this->actingAs($owner)
            ->post($this->storeUrl($team), $this->payload([
                'event_types' => [$weddings->id],
            ]))
            ->assertSessionHasErrors('event_types.0');

        $this->assertDatabaseCount('listings', 0);

        $this->actingAs($owner)
            ->get(route('listings.create', ['current_team' => $team->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('eventTypes', 12)
                ->where('eventTypes', fn ($types) => collect($types)
                    ->pluck('slug')
                    ->doesntContain('bodas'))
                ->has('featuredCharacteristics', 3)
                ->where('featuredCharacteristics', fn ($items) => collect($items)
                    ->pluck('key')
                    ->doesntContain('green_areas'))
                ->where('serviceGroups', fn ($groups) => collect($groups)
                    ->flatMap(fn ($group) => $group['services'])
                    ->pluck('value')
                    ->contains('green_areas')));
    }

    public function test_owner_form_does_not_duplicate_featured_characteristics_in_general_services(): void
    {
        [$owner, $team] = $this->ownerWithTeam();

        $this->actingAs($owner)
            ->get(route('listings.create', ['current_team' => $team->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('featuredCharacteristics', 4)
                ->has('eventTypes', 13)
                ->where('serviceGroups', fn ($groups) => collect($groups)
                    ->flatMap(fn ($group) => $group['services'])
                    ->pluck('value')
                    ->intersect(['garden', 'pool', 'covered_terrace', 'green_areas'])
                    ->isEmpty()));
    }

    public function test_existing_listing_without_event_types_remains_unchanged_until_the_owner_configures_them(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $listing = Listing::factory()->forTeam($team)->create();

        $this->assertSame([], $listing->eventTypes()->pluck('slug')->all());

        $this->actingAs($owner)
            ->get(route('listings.edit', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('listing.event_types', []));
    }

    public function test_public_listing_shows_only_declared_active_event_types(): void
    {
        [, $team] = $this->ownerWithTeam();
        $weddings = $this->eventType('bodas');
        $birthdays = $this->eventType('cumpleanos');
        $listing = $this->publishedListing(
            $team,
            'Terraza con eventos',
            ['garden'],
            [$weddings, $birthdays],
        );

        $this->get(route('public.listings.show', ['listing' => $listing->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('listing.event_types', 2)
                ->where('listing.event_types.0.slug', 'bodas')
                ->where('listing.event_types.1.slug', 'cumpleanos'));

        $weddings->update(['is_active' => false]);

        $this->get(route('public.listings.show', ['listing' => $listing->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('listing.event_types', 1)
                ->where('listing.event_types.0.slug', 'cumpleanos'));
    }

    public function test_inactive_existing_event_type_is_preserved_when_owner_updates_the_listing(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $weddings = $this->eventType('bodas');
        $birthdays = $this->eventType('cumpleanos');
        $listing = Listing::factory()->forTeam($team)->create();
        $listing->eventTypes()->sync([$weddings->id, $birthdays->id]);
        $weddings->update(['is_active' => false]);

        $this->actingAs($owner)
            ->get(route('listings.edit', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('listing.event_types', [$birthdays->id]));

        $this->actingAs($owner)
            ->patch($this->updateUrl($team, $listing), $this->payload([
                'event_types' => [$birthdays->id],
            ]))
            ->assertRedirect();

        $this->assertSame(
            ['bodas', 'cumpleanos'],
            $listing->fresh()->eventTypes->pluck('slug')->sort()->values()->all(),
        );
    }

    /**
     * @return array{0: User, 1: Team}
     */
    private function ownerWithTeam(): array
    {
        $owner = User::factory()->create();

        return [$owner, $owner->currentTeam];
    }

    private function eventType(string $slug): ListingEventType
    {
        return ListingEventType::query()->where('slug', $slug)->firstOrFail();
    }

    /**
     * @param  array<int, string>  $services
     * @param  array<int, ListingEventType>  $eventTypes
     */
    private function publishedListing(
        Team $team,
        string $name,
        array $services,
        array $eventTypes = [],
        string $state = 'Jalisco',
        string $city = 'Guadalajara',
    ): Listing {
        $listing = Listing::factory()->forTeam($team)->published()->create([
            'name' => $name,
            'services' => $services,
            'state' => $state,
            'city' => $city,
        ]);

        if ($eventTypes !== []) {
            $listing->eventTypes()->sync(
                array_map(fn (ListingEventType $eventType): int => $eventType->id, $eventTypes),
            );
        }

        return $listing;
    }

    private function storeUrl(Team $team): string
    {
        return route('listings.store', ['current_team' => $team->slug]);
    }

    private function updateUrl(Team $team, Listing $listing): string
    {
        return route('listings.update', [
            'current_team' => $team->slug,
            'listing' => $listing->slug,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Terraza de Prueba',
            'description' => 'Una terraza para eventos.',
            'address' => 'Av. Reforma 123',
            'city' => 'Guadalajara',
            'state' => 'Jalisco',
            'country' => 'México',
            'postal_code' => '44100',
            'latitude' => 20.6736,
            'longitude' => -103.3440,
            'capacity' => 60,
            'services' => ['wifi', 'bathroom'],
            'event_types' => [],
            'rules' => 'No fumar.',
            'contact_name' => 'Ana',
            'contact_email' => 'ana@example.test',
            'contact_phone' => '5555555555',
            'whatsapp_phone' => '5215555555555',
        ], $overrides);
    }
}
