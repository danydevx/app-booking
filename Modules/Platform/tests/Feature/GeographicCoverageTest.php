<?php

namespace Modules\Platform\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Listings\Enums\ListingStatus;
use Modules\Listings\Models\Listing;
use Modules\Listings\Models\ListingMedia;
use Modules\Platform\Database\Seeders\GeographicCatalogSeeder;
use Modules\Platform\Models\GeoMunicipality;
use Modules\Platform\Models\GeoState;
use Modules\Platform\Models\GeoStateCoverage;
use Modules\Platform\Support\GeographicCoverageService;
use Modules\Platform\Support\PlatformAccess;
use Tests\TestCase;

class GeographicCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        GeographicCoverageService::flushCache();
    }

    protected function tearDown(): void
    {
        GeographicCoverageService::flushCache();

        parent::tearDown();
    }

    public function test_jalisco_is_the_only_initially_enabled_state_and_filters_the_catalog(): void
    {
        $jaliscoCoverage = GeoStateCoverage::query()
            ->whereHas('state', fn ($query) => $query->where('code', '14'))
            ->firstOrFail();

        $this->assertTrue($jaliscoCoverage->enabled);
        $this->assertTrue($jaliscoCoverage->all_municipalities);

        $locations = app(GeographicCoverageService::class)->locations();

        $this->assertArrayHasKey('Jalisco', $locations);
        $this->assertCount(125, $locations['Jalisco']['municipalities']);

        [$jaliscoOwner, $jaliscoTeam, $jaliscoListing] = $this->publishedListing('Jalisco', 'Guadalajara');
        [, , $cdmxListing] = $this->publishedListing('CDMX', 'Cuauhtémoc');

        $this->get(route('public.listings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.name', $jaliscoListing->name)
                ->has('locations', 1)
                ->has('locations.Jalisco')
                ->missing('locations.CDMX')
                ->missing('locations.Ciudad de México'));

        $this->get(route('public.listings.show', ['listing' => $jaliscoListing->slug]))
            ->assertOk();

        $this->get(route('public.listings.show', ['listing' => $cdmxListing->slug]))
            ->assertNotFound();

        $this->get(route('public.listings.index', ['state' => 'CDMX']))
            ->assertSessionHasErrors('state');

        $this->get(route('public.listings.index', ['state' => 'Jalisco', 'city' => 'Cuauhtémoc']))
            ->assertSessionHasErrors('city');

        $this->assertTrue(
            Listing::query()
                ->publiclyVisible()
                ->where('state', 'Jalisco')
                ->where('id', $jaliscoListing->id)
                ->exists(),
        );

        $this->assertFalse(
            Listing::query()
                ->publiclyVisible()
                ->whereIn('state', ['CDMX', 'Ciudad de México'])
                ->exists(),
        );

        $this->assertSame($jaliscoTeam->id, $jaliscoOwner->currentTeam?->id);
    }

    public function test_los_cabos_can_be_enabled_without_enabling_all_of_baja_california_sur(): void
    {
        $superadmin = $this->superadmin();
        $coverage = app(GeographicCoverageService::class);
        $baja = GeoState::query()->where('code', '03')->firstOrFail();
        $losCabos = GeoMunicipality::query()
            ->where('geo_state_id', $baja->id)
            ->where('name', 'Los Cabos')
            ->firstOrFail();

        $coverage->updateCoverage([[
            'id' => $baja->id,
            'enabled' => true,
            'all_municipalities' => false,
            'municipality_ids' => [$losCabos->id],
        ]], $superadmin);

        [, , $losCabosListing] = $this->publishedListing('Baja California Sur', 'Los Cabos');
        [, , $laPazListing] = $this->publishedListing('Baja California Sur', 'La Paz');

        $this->get(route('public.listings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.name', $losCabosListing->name)
                ->has('locations.Baja California Sur')
                ->has('locations.Baja California Sur.municipalities', 1)
                ->where('locations.Baja California Sur.municipalities.0', 'Los Cabos'));

        $this->get(route('public.listings.show', ['listing' => $losCabosListing->slug]))
            ->assertOk();

        $this->get(route('public.listings.show', ['listing' => $laPazListing->slug]))
            ->assertNotFound();

        $this->get(route('public.listings.index', [
            'state' => 'Baja California Sur',
            'city' => 'La Paz',
        ]))->assertSessionHasErrors('city');
    }

    public function test_disabling_a_state_hides_its_listings_and_reactivating_restores_them(): void
    {
        $superadmin = $this->superadmin();
        [$owner, $team, $listing] = $this->publishedListing('Jalisco', 'Guadalajara');
        $coverage = app(GeographicCoverageService::class);
        $jalisco = GeoState::query()->where('code', '14')->firstOrFail();

        $coverage->updateCoverage([[
            'id' => $jalisco->id,
            'enabled' => false,
            'all_municipalities' => false,
            'municipality_ids' => [],
        ]], $superadmin);

        $this->get(route('public.listings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('listings.data', 0));

        $this->get(route('public.listings.show', ['listing' => $listing->slug]))
            ->assertNotFound();

        $this->get(route('public.bookings.availability', ['listing' => $listing->slug]))
            ->assertNotFound();

        $this->assertDatabaseHas('listings', ['id' => $listing->id]);

        $this->actingAs($owner)
            ->get(route('listings.manage', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]))
            ->assertOk();

        $this->actingAs($superadmin)
            ->get(route('platform.listings.show', ['listing' => $listing->slug]))
            ->assertOk();

        $coverage->updateCoverage([[
            'id' => $jalisco->id,
            'enabled' => true,
            'all_municipalities' => true,
            'municipality_ids' => [],
        ]], $superadmin);

        $this->get(route('public.listings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.name', $listing->name));

        $this->get(route('public.listings.show', ['listing' => $listing->slug]))
            ->assertOk();
    }

    public function test_reactivating_a_state_restores_listings_with_legacy_state_values(): void
    {
        $superadmin = $this->superadmin();
        $coverage = app(GeographicCoverageService::class);
        $cdmx = GeoState::query()->where('code', '09')->firstOrFail();
        [, , $listing] = $this->publishedListing('CDMX', 'Cuauhtémoc');

        $this->get(route('public.listings.index', ['state' => 'Ciudad de México']))
            ->assertSessionHasErrors('state');

        $coverage->updateCoverage([[
            'id' => $cdmx->id,
            'enabled' => true,
            'all_municipalities' => true,
            'municipality_ids' => [],
        ]], $superadmin);

        $this->get(route('public.listings.show', ['listing' => $listing->slug]))
            ->assertOk();

        $this->get(route('public.listings.index', ['state' => 'Ciudad de México']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.name', $listing->name));
    }

    public function test_owner_cannot_publish_or_create_outside_coverage(): void
    {
        [$owner, $team] = $this->ownerWithTeam();

        $this->actingAs($owner)
            ->post(route('listings.store', ['current_team' => $team->slug]), [
                'name' => 'Terraza fuera de cobertura',
                'state' => 'CDMX',
                'city' => 'Cuauhtémoc',
                'country' => 'México',
            ])
            ->assertSessionHasErrors('state');

        $listing = Listing::factory()->forTeam($team)->create([
            'state' => 'CDMX',
            'city' => 'Cuauhtémoc',
            'status' => ListingStatus::Draft,
        ]);
        ListingMedia::factory()->for($listing)->create(['type' => 'image']);

        $this->actingAs($owner)
            ->patch(route('listings.publish', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]))
            ->assertSessionHasErrors('listing');

        $this->assertSame(ListingStatus::Draft, $listing->fresh()->status);
    }

    public function test_owner_cannot_access_or_modify_geographic_coverage(): void
    {
        $owner = User::factory()->create();
        $jalisco = GeoState::query()->where('code', '14')->firstOrFail();

        $this->actingAs($owner)
            ->get(route('platform.coverage.edit'))
            ->assertForbidden();

        $this->actingAs($owner)
            ->patch(route('platform.coverage.update'), [
                'states' => [[
                    'id' => $jalisco->id,
                    'enabled' => false,
                    'all_municipalities' => false,
                    'municipality_ids' => [],
                ]],
            ])
            ->assertForbidden();
    }

    public function test_superadmin_can_open_and_update_coverage_through_the_panel(): void
    {
        $superadmin = $this->superadmin();

        $this->actingAs($superadmin)
            ->get(route('platform.coverage.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Platform/Coverage/Index')
                ->has('states', 32));

        $this->actingAs($superadmin)
            ->patch(route('platform.coverage.update'), [
                'states' => [[
                    'id' => GeoState::query()->where('code', '14')->value('id'),
                    'enabled' => false,
                    'all_municipalities' => false,
                    'municipality_ids' => [],
                ]],
            ])
            ->assertRedirect();

        $this->assertFalse(
            GeoStateCoverage::query()
                ->whereHas('state', fn ($query) => $query->where('code', '14'))
                ->firstOrFail()
                ->enabled,
        );

        $this->assertDatabaseHas('activity_log', [
            'description' => 'geographic-coverage.updated',
        ]);
    }

    public function test_seeder_does_not_overwrite_existing_coverage_configuration(): void
    {
        $superadmin = $this->superadmin();
        $coverage = app(GeographicCoverageService::class);
        $jalisco = GeoState::query()->where('code', '14')->firstOrFail();

        $coverage->updateCoverage([[
            'id' => $jalisco->id,
            'enabled' => false,
            'all_municipalities' => false,
            'municipality_ids' => [],
        ]], $superadmin);

        $this->seed(GeographicCatalogSeeder::class);
        $this->seed(GeographicCatalogSeeder::class);

        $this->assertFalse($jalisco->coverage()->firstOrFail()->enabled);
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
     * @return array{0: User, 1: Team, 2: Listing}
     */
    private function publishedListing(string $state, string $city): array
    {
        $owner = User::factory()->create();
        $team = $owner->currentTeam;
        $listing = Listing::factory()->forTeam($team)->published()->create([
            'state' => $state,
            'city' => $city,
        ]);

        return [$owner, $team, $listing];
    }

    private function superadmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(PlatformAccess::ensure());

        return $user;
    }
}
