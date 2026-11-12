<?php

namespace Modules\Listings\Tests\Feature;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Laravel\Cashier\Subscription;
use Modules\Listings\Enums\ListingStatus;
use Modules\Listings\Models\Listing;
use Modules\Listings\Models\ListingMedia;
use Tests\TestCase;

class ListingManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_a_listing_as_a_draft(): void
    {
        [$owner, $team] = $this->ownerWithTeam();

        $response = $this->actingAs($owner)->post(
            route('listings.store', ['current_team' => $team->slug]),
            $this->payload(['name' => 'Terraza Centro']),
        );

        $listing = Listing::query()->firstOrFail();

        $response->assertRedirect(route('listings.edit', [
            'current_team' => $team->slug,
            'listing' => $listing->slug,
        ]));

        $this->assertSame($team->id, $listing->team_id);
        $this->assertSame('terraza-centro', $listing->slug);
        $this->assertSame(ListingStatus::Draft, $listing->status);
        $this->assertSame('America/Mexico_City', $listing->timezone);
        $this->assertNull($listing->published_at);
    }

    public function test_owner_can_update_own_listing(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $listing = Listing::factory()->forTeam($team)->create([
            'name' => 'Terraza Norte',
        ]);
        $originalSlug = $listing->slug;

        $this->actingAs($owner)
            ->patch(route('listings.update', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]), $this->payload([
                'name' => 'Terraza Norte Actualizada',
                'city' => 'Guadalajara',
                'state' => 'Jalisco',
                'capacity' => 80,
            ]))
            ->assertRedirect();

        $listing->refresh();

        $this->assertSame('Terraza Norte Actualizada', $listing->name);
        $this->assertSame('Guadalajara', $listing->city);
        $this->assertSame('Jalisco', $listing->state);
        $this->assertSame(80, $listing->capacity);
        $this->assertSame($originalSlug, $listing->slug);
    }

    public function test_city_must_belong_to_the_selected_state(): void
    {
        [$owner, $team] = $this->ownerWithTeam();

        $this->actingAs($owner)
            ->post(route('listings.store', ['current_team' => $team->slug]), $this->payload([
                'city' => 'Guadalajara',
                'state' => 'CDMX',
            ]))
            ->assertSessionHasErrors('city');

        $this->assertDatabaseCount('listings', 0);
    }

    public function test_state_and_municipality_follow_the_location_hierarchy(): void
    {
        [$owner, $team] = $this->ownerWithTeam();

        $this->actingAs($owner)
            ->post(route('listings.store', ['current_team' => $team->slug]), $this->payload([
                'country' => null,
                'state' => 'CDMX',
                'city' => 'Cuauhtémoc',
            ]))
            ->assertSessionHasErrors('country');

        $this->actingAs($owner)
            ->post(route('listings.store', ['current_team' => $team->slug]), $this->payload([
                'state' => '',
                'city' => 'Guadalajara',
            ]))
            ->assertSessionHasErrors('state');
    }

    public function test_only_supported_countries_are_accepted(): void
    {
        [$owner, $team] = $this->ownerWithTeam();

        $this->actingAs($owner)
            ->post(route('listings.store', ['current_team' => $team->slug]), $this->payload([
                'country' => 'España',
            ]))
            ->assertSessionHasErrors('country');

        $this->assertDatabaseCount('listings', 0);
    }

    public function test_only_supported_states_are_accepted(): void
    {
        [$owner, $team] = $this->ownerWithTeam();

        $this->actingAs($owner)
            ->post(route('listings.store', ['current_team' => $team->slug]), $this->payload([
                'city' => 'Monterrey',
                'state' => 'Nuevo León',
            ]))
            ->assertSessionHasErrors('state');
    }

    public function test_user_cannot_edit_a_listing_from_another_team_even_by_guessing_the_slug(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $otherTeam = Team::factory()->create();
        $foreignListing = Listing::factory()->forTeam($otherTeam)->create();

        $this->actingAs($owner)
            ->get(route('listings.edit', [
                'current_team' => $team->slug,
                'listing' => $foreignListing->slug,
            ]))
            ->assertNotFound();
    }

    public function test_owner_can_publish_a_complete_listing(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $listing = Listing::factory()->forTeam($team)->create([
            'address' => 'Av. Principal 123',
            'latitude' => 19.4326,
            'longitude' => -99.1332,
            'contact_email' => 'hola@terraza.test',
        ]);

        ListingMedia::factory()->for($listing)->create([
            'type' => 'image',
        ]);

        $this->actingAs($owner)
            ->patch(route('listings.publish', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]))
            ->assertRedirect();

        $listing->refresh();

        $this->assertSame(ListingStatus::Published, $listing->status);
        $this->assertNotNull($listing->published_at);
    }

    public function test_publish_requires_an_image_address_coordinates_and_contact(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $listing = Listing::factory()->forTeam($team)->create([
            'address' => null,
            'latitude' => null,
            'longitude' => null,
            'contact_email' => null,
            'contact_phone' => null,
            'whatsapp_phone' => null,
        ]);

        $this->actingAs($owner)
            ->patch(route('listings.publish', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]))
            ->assertSessionHasErrors('listing');

        $this->assertSame(ListingStatus::Draft, $listing->fresh()->status);
    }

    public function test_duplicate_names_receive_deterministic_slugs(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $this->subscribePro($team);

        $this->actingAs($owner)->post(
            route('listings.store', ['current_team' => $team->slug]),
            $this->payload(['name' => 'Terraza Centro']),
        );

        $this->actingAs($owner)->post(
            route('listings.store', ['current_team' => $team->slug]),
            $this->payload(['name' => 'Terraza Centro']),
        );

        $this->assertDatabaseHas('listings', ['slug' => 'terraza-centro']);
        $this->assertDatabaseHas('listings', ['slug' => 'terraza-centro-2']);
    }

    public function test_member_can_create_but_cannot_publish(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $member = $this->memberOf($team);

        $this->actingAs($member)->post(
            route('listings.store', ['current_team' => $team->slug]),
            $this->payload(['name' => 'Terraza Equipo']),
        )->assertRedirect();

        $listing = Listing::query()->where('name', 'Terraza Equipo')->firstOrFail();

        $this->actingAs($member)
            ->patch(route('listings.publish', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]))
            ->assertForbidden();
    }

    public function test_owner_listing_index_only_lists_current_team_listings(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $ownListing = Listing::factory()->forTeam($team)->create();
        $otherTeam = Team::factory()->create();
        Listing::factory()->forTeam($otherTeam)->create();

        $this->actingAs($owner)
            ->get(route('listings.index', ['current_team' => $team->slug]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Listings/Index')
                ->has('listings.data', 1)
                ->where('listings.data.0.id', $ownListing->id));
    }

    public function test_owner_listing_index_eager_loads_cover_images_and_counts(): void
    {
        Model::preventLazyLoading();

        [$owner, $team] = $this->ownerWithTeam();

        Listing::factory()
            ->forTeam($team)
            ->count(5)
            ->create()
            ->each(fn (Listing $listing) => ListingMedia::factory()
                ->for($listing)
                ->create());

        $this->actingAs($owner)
            ->get(route('listings.index', ['current_team' => $team->slug]))
            ->assertOk();

        Model::preventLazyLoading(false);
    }

    public function test_photos_are_managed_from_their_own_page(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $listing = Listing::factory()->forTeam($team)->create();
        $media = ListingMedia::factory()->for($listing)->create();

        $this->actingAs($owner)
            ->get(route('listings.photos.index', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Listings/Photos')
                ->has('media', 1)
                ->where('media.0.id', $media->id)
                ->has('limits')
                ->where('urls.photos', route('listings.photos.index', [
                    'current_team' => $team->slug,
                    'listing' => $listing->slug,
                ]))
                ->where('urls.edit', route('listings.edit', [
                    'current_team' => $team->slug,
                    'listing' => $listing->slug,
                ])));
    }

    public function test_deleting_a_listing_removes_its_media_files(): void
    {
        Storage::fake('public');

        [$owner, $team] = $this->ownerWithTeam();
        $listing = Listing::factory()->forTeam($team)->create();
        $path = "listings/{$listing->id}/photo.jpg";
        Storage::disk('public')->put($path, 'image-content');

        $media = ListingMedia::factory()->for($listing)->create([
            'disk' => 'public',
            'path' => $path,
        ]);

        $this->actingAs($owner)
            ->delete(route('listings.destroy', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]))
            ->assertRedirect(route('listings.index', ['current_team' => $team->slug]));

        $this->assertDatabaseMissing('listings', ['id' => $listing->id]);
        $this->assertDatabaseMissing('listing_media', ['id' => $media->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_owner_can_save_terrace_details(): void
    {
        [$owner, $team] = $this->ownerWithTeam();

        $this->actingAs($owner)->post(
            route('listings.store', ['current_team' => $team->slug]),
            $this->payload([
                'details' => [
                    'space_type' => 'rooftop',
                    'access' => 'Elevador',
                    'view' => 'Panorámica',
                ],
            ]),
        )->assertRedirect();

        $listing = Listing::query()->firstOrFail();

        $this->assertSame('rooftop', $listing->details['space_type']);
        $this->assertSame('Elevador', $listing->details['access']);
        $this->assertSame('Panorámica', $listing->details['view']);
    }

    public function test_listing_rejects_unknown_detail_keys(): void
    {
        [$owner, $team] = $this->ownerWithTeam();

        $this->actingAs($owner)->post(
            route('listings.store', ['current_team' => $team->slug]),
            $this->payload(['details' => ['unknown' => 'value']]),
        )->assertSessionHasErrors('details');

        $this->assertDatabaseCount('listings', 0);
    }

    public function test_listing_rejects_invalid_select_detail_values(): void
    {
        [$owner, $team] = $this->ownerWithTeam();

        $this->actingAs($owner)->post(
            route('listings.store', ['current_team' => $team->slug]),
            $this->payload(['details' => ['space_type' => 'invalid']]),
        )->assertSessionHasErrors('details.space_type');

        $this->assertDatabaseCount('listings', 0);
    }

    public function test_public_listing_exposes_terrace_details(): void
    {
        $team = Team::factory()->create();
        $listing = Listing::factory()->forTeam($team)->published()->create([
            'details' => [
                'space_type' => 'rooftop',
                'view' => 'Panorámica',
            ],
        ]);

        $this->get(route('public.listings.show', ['listing' => $listing->slug]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Listings/Public/Show')
                ->has('listing.details', 2)
                ->where('listing.details.0.key', 'space_type')
                ->where('listing.details.0.label', 'Tipo de espacio')
                ->where('listing.details.0.value', 'Rooftop'));
    }

    /**
     * @return array{0: User, 1: Team}
     */
    private function ownerWithTeam(): array
    {
        $owner = User::factory()->create();

        return [$owner, $owner->currentTeam];
    }

    private function subscribePro(Team $team): void
    {
        config(['billing.plans.pro.stripe_price_id' => 'price_pro_test']);

        Subscription::query()->create([
            'team_id' => $team->id,
            'type' => 'default',
            'stripe_id' => 'sub_'.fake()->unique()->bothify('??####'),
            'stripe_status' => 'active',
            'stripe_price' => 'price_pro_test',
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null,
        ]);
    }

    private function memberOf(Team $team): User
    {
        $member = User::factory()->create();
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);
        $member->switchTeam($team);

        return $member;
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
            'city' => 'Cuauhtémoc',
            'state' => 'CDMX',
            'country' => 'México',
            'postal_code' => '06000',
            'latitude' => 19.4326,
            'longitude' => -99.1332,
            'capacity' => 60,
            'services' => ['wifi', 'bathroom'],
            'rules' => 'No fumar.',
            'contact_name' => 'Ana',
            'contact_email' => 'ana@example.test',
            'contact_phone' => '5555555555',
            'whatsapp_phone' => '5215555555555',
        ], $overrides);
    }
}
