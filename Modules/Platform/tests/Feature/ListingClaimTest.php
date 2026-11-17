<?php

namespace Modules\Platform\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Bookings\Actions\CreateBookingInquiry;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Notifications\NewInquiryDirectoryNotification;
use Modules\Listings\Models\Listing;
use Modules\Listings\Notifications\ListingReturnedToDirectory;
use Modules\Platform\Enums\ListingClaimStatus;
use Modules\Platform\Models\ListingClaim;
use Modules\Platform\Notifications\ListingClaimApproved;
use Modules\Platform\Notifications\ListingClaimRejected;
use Modules\Platform\Notifications\ListingClaimSubmitted;
use Modules\Platform\Support\PlatformAccess;
use Tests\TestCase;

class ListingClaimTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_detail_marks_directory_listings_as_claimable_and_hides_imported_contact(): void
    {
        [, , $listing] = $this->claimableListing();

        $this->get(route('public.listings.show', ['listing' => $listing->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listings/Public/Show')
                ->where('listing.is_claimable', true)
                ->where('listing.is_claimed', false)
                ->where('listing.claim_url', route('public.claims.create', ['listing' => $listing->slug]))
                ->where('listing.contact_name', null)
                ->where('listing.contact_email', null)
                ->where('listing.contact_phone', null)
                ->where('listing.whatsapp_url', null)
                ->where('listing.account.name', null)
                ->where('listing.account.contact_email', null));
    }

    public function test_guest_can_open_the_claim_page_but_cannot_submit_without_authentication(): void
    {
        [, , $listing] = $this->claimableListing();

        $this->get(route('public.claims.create', ['listing' => $listing->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Platform/Public/Claim')
                ->where('authenticated', false)
                ->has('roles', 3));

        $this->post(route('public.claims.store', ['listing' => $listing->slug]), $this->claimPayload())
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('listing_claims', 0);
    }

    public function test_verified_user_can_submit_a_claim_with_evidence(): void
    {
        Notification::fake();
        Storage::fake('local');

        [$claimant] = $this->ownerWithTeam();
        [, , $listing] = $this->claimableListing();

        $this->actingAs($claimant)
            ->post(route('public.claims.store', ['listing' => $listing->slug]), $this->claimPayload([
                'evidence' => UploadedFile::fake()->create('predial.pdf', 120, 'application/pdf'),
            ]))
            ->assertRedirect(route('public.listings.show', ['listing' => $listing->slug]));

        $claim = ListingClaim::query()->firstOrFail();

        $this->assertSame($listing->id, $claim->listing_id);
        $this->assertSame($claimant->id, $claim->user_id);
        $this->assertSame($claimant->email, $claim->claimant_email);
        $this->assertSame(ListingClaimStatus::Pending, $claim->status);
        $this->assertNotNull($claim->evidence_path);
        $this->assertSame('predial.pdf', $claim->evidence_name);
        Storage::disk('local')->assertExists($claim->evidence_path);

        Notification::assertSentOnDemand(ListingClaimSubmitted::class);
    }

    public function test_user_cannot_have_two_pending_claims_for_the_same_listing(): void
    {
        Notification::fake();

        [$claimant] = $this->ownerWithTeam();
        [, , $listing] = $this->claimableListing();

        ListingClaim::factory()
            ->forListing($listing)
            ->forUser($claimant)
            ->create();

        $this->actingAs($claimant)
            ->post(route('public.claims.store', ['listing' => $listing->slug]), $this->claimPayload())
            ->assertSessionHasErrors('listing');

        $this->assertSame(1, ListingClaim::query()->count());
    }

    public function test_user_cannot_claim_to_an_account_they_do_not_manage(): void
    {
        [$claimant] = $this->ownerWithTeam();
        [$otherOwner] = $this->ownerWithTeam();
        [, , $listing] = $this->claimableListing();

        $this->actingAs($claimant)
            ->post(route('public.claims.store', ['listing' => $listing->slug]), $this->claimPayload([
                'target_team_id' => $otherOwner->currentTeam->id,
            ]))
            ->assertSessionHasErrors('target_team_id');

        $this->assertDatabaseCount('listing_claims', 0);
    }

    public function test_listings_owned_by_a_normal_account_are_not_claimable(): void
    {
        [$claimant] = $this->ownerWithTeam();
        [, $team] = $this->ownerWithTeam();
        $listing = Listing::factory()->forTeam($team)->published()->create([
            'claimed_at' => now(),
        ]);

        $this->actingAs($claimant)
            ->post(route('public.claims.store', ['listing' => $listing->slug]), $this->claimPayload())
            ->assertSessionHasErrors('listing');

        $this->assertDatabaseCount('listing_claims', 0);
    }

    public function test_claim_page_redirects_when_the_listing_was_already_claimed(): void
    {
        [, , $listing] = $this->claimableListing();
        $listing->forceFill(['claimed_at' => now()])->save();

        $this->get(route('public.claims.create', ['listing' => $listing->slug]))
            ->assertRedirect(route('public.listings.show', ['listing' => $listing->slug]));
    }

    public function test_normal_owners_cannot_access_claim_moderation(): void
    {
        [$owner] = $this->ownerWithTeam();
        [, , $listing] = $this->claimableListing();
        $claim = ListingClaim::factory()->forListing($listing)->create();

        $this->actingAs($owner)->get(route('platform.claims.index'))->assertForbidden();
        $this->actingAs($owner)->get(route('platform.claims.show', ['claim' => $claim->id]))->assertForbidden();
        $this->actingAs($owner)->post(route('platform.claims.approve', ['claim' => $claim->id]))->assertForbidden();
        $this->actingAs($owner)->post(route('platform.claims.reject', ['claim' => $claim->id]), [
            'reason' => 'No autorizado',
        ])->assertForbidden();
    }

    public function test_superadmin_approval_creates_a_business_account_and_transfers_the_listing(): void
    {
        Notification::fake();

        [$claimant] = $this->ownerWithTeam();
        [, $directory, $listing] = $this->claimableListing();
        $superadmin = $this->superadmin();

        $customer = Customer::factory()->create([
            'team_id' => $directory->id,
            'listing_id' => $listing->id,
        ]);
        $inquiry = BookingInquiry::factory()
            ->forListing($listing)
            ->create(['customer_id' => $customer->id]);

        $claim = ListingClaim::factory()
            ->forListing($listing)
            ->forUser($claimant)
            ->create([
                'business_name' => 'Jardín Los Encinos',
                'target_team_id' => null,
            ]);

        $this->actingAs($superadmin)
            ->post(route('platform.claims.approve', ['claim' => $claim->id]))
            ->assertRedirect(route('platform.claims.show', ['claim' => $claim->id]));

        $claim->refresh();
        $listing->refresh();

        $this->assertSame(ListingClaimStatus::Approved, $claim->status);
        $this->assertNotNull($claim->target_team_id);
        $this->assertSame($superadmin->id, $claim->reviewed_by_id);

        $this->assertSame($claim->target_team_id, $listing->team_id);
        $this->assertNotNull($listing->claimed_at);
        $this->assertSame($claimant->id, $listing->claimed_by_user_id);
        $this->assertSame($claim->claimant_name, $listing->contact_name);
        $this->assertSame($claimant->email, $listing->contact_email);
        $this->assertSame($claim->claimant_phone, $listing->contact_phone);
        $this->assertNull($listing->whatsapp_phone);
        $this->assertTrue($claimant->belongsToTeam(Team::query()->findOrFail($claim->target_team_id)));
        $this->assertSame('Jardín Los Encinos', $claim->targetTeam->name);

        $this->assertSame($claim->target_team_id, $inquiry->fresh()->team_id);
        $this->assertSame($claim->target_team_id, $customer->fresh()->team_id);

        Notification::assertSentTo($claimant, ListingClaimApproved::class);
    }

    public function test_approval_rejects_other_pending_claims_for_the_same_listing(): void
    {
        Notification::fake();

        [$claimant] = $this->ownerWithTeam();
        [$otherClaimant] = $this->ownerWithTeam();
        [, , $listing] = $this->claimableListing();
        $superadmin = $this->superadmin();

        $claim = ListingClaim::factory()->forListing($listing)->forUser($claimant)->create();
        $other = ListingClaim::factory()->forListing($listing)->forUser($otherClaimant)->create();

        $this->actingAs($superadmin)
            ->post(route('platform.claims.approve', ['claim' => $claim->id]))
            ->assertRedirect();

        $this->assertSame(ListingClaimStatus::Approved, $claim->fresh()->status);
        $this->assertSame(ListingClaimStatus::Rejected, $other->fresh()->status);
        $this->assertSame(
            'La terraza ya fue reclamada por otro propietario.',
            $other->fresh()->rejection_reason,
        );

        Notification::assertSentTo($otherClaimant, ListingClaimRejected::class);
    }

    public function test_approval_can_use_an_existing_account_owned_by_the_claimant(): void
    {
        Notification::fake();

        [$claimant] = $this->ownerWithTeam();
        $targetTeam = Team::factory()->create(['name' => 'Salón Jardín']);
        $targetTeam->members()->attach($claimant, ['role' => 'owner']);
        [, , $listing] = $this->claimableListing();

        $claim = ListingClaim::factory()
            ->forListing($listing)
            ->forUser($claimant)
            ->create(['target_team_id' => $targetTeam->id]);

        $this->actingAs($this->superadmin())
            ->post(route('platform.claims.approve', ['claim' => $claim->id]))
            ->assertRedirect();

        $this->assertSame($targetTeam->id, $listing->fresh()->team_id);
        $this->assertSame($targetTeam->id, $claim->fresh()->target_team_id);
    }

    public function test_superadmin_can_reject_a_claim_with_a_reason(): void
    {
        Notification::fake();

        [$claimant] = $this->ownerWithTeam();
        [, , $listing] = $this->claimableListing();
        $claim = ListingClaim::factory()->forListing($listing)->forUser($claimant)->create();

        $this->actingAs($this->superadmin())
            ->post(route('platform.claims.reject', ['claim' => $claim->id]), [
                'reason' => 'La documentación no acredita la propiedad.',
            ])
            ->assertRedirect(route('platform.claims.show', ['claim' => $claim->id]));

        $claim->refresh();

        $this->assertSame(ListingClaimStatus::Rejected, $claim->status);
        $this->assertSame('La documentación no acredita la propiedad.', $claim->rejection_reason);
        $this->assertNotNull($claim->reviewed_at);
        $this->assertNull($listing->fresh()->claimed_at);

        Notification::assertSentTo($claimant, ListingClaimRejected::class);
    }

    public function test_superadmin_can_mark_an_existing_listing_as_claimable(): void
    {
        Notification::fake();

        [, $team] = $this->ownerWithTeam();
        $listing = Listing::factory()->forTeam($team)->published()->create([
            'claimed_at' => now(),
            'claimed_by_user_id' => null,
        ]);

        $directory = Team::factory()->directory()->create(['name' => 'Directorio']);

        $this->actingAs($this->superadmin())
            ->post(route('platform.listings.claimable', ['listing' => $listing->slug]), [
                'reason' => 'Se cargará desde el Directorio.',
            ])
            ->assertRedirect();

        $listing->refresh();

        $this->assertSame($directory->id, $listing->team_id);
        $this->assertNull($listing->claimed_at);
        $this->assertNull($listing->claimed_by_user_id);

        $this->get(route('public.listings.show', ['listing' => $listing->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('listing.is_claimable', true)
                ->where('listing.claim_url', route('public.claims.create', ['listing' => $listing->slug])));
    }

    public function test_superadmin_can_return_a_claimed_listing_to_the_directory(): void
    {
        Notification::fake();

        [$claimant, , $listing] = $this->claimableListing();
        $superadmin = $this->superadmin();
        $targetTeam = Team::factory()->create(['name' => 'Cuenta del propietario']);
        $targetTeam->members()->attach($claimant, ['role' => 'owner']);

        $listing->forceFill([
            'team_id' => $targetTeam->id,
            'claimed_at' => now(),
            'claimed_by_user_id' => $claimant->id,
        ])->save();

        $directory = Team::query()->where('is_directory', true)->firstOrFail();

        $this->actingAs($superadmin)
            ->post(route('platform.listings.release', ['listing' => $listing->slug]), [
                'reason' => 'La verificación fue insuficiente.',
            ])
            ->assertRedirect();

        $listing->refresh();

        $this->assertSame($directory->id, $listing->team_id);
        $this->assertNull($listing->claimed_at);
        $this->assertNull($listing->claimed_by_user_id);

        Notification::assertSentTo($claimant, ListingReturnedToDirectory::class);
    }

    public function test_release_rejects_listings_created_by_their_own_account(): void
    {
        [, $team] = $this->ownerWithTeam();
        $listing = Listing::factory()->forTeam($team)->published()->create([
            'claimed_at' => now(),
            'claimed_by_user_id' => null,
        ]);

        $this->actingAs($this->superadmin())
            ->post(route('platform.listings.release', ['listing' => $listing->slug]), [
                'reason' => 'Intento de liberar una terraza propia.',
            ])
            ->assertSessionHasErrors('reason');

        $this->assertSame($team->id, $listing->fresh()->team_id);
        $this->assertNotNull($listing->fresh()->claimed_at);
    }

    public function test_directory_inquiries_notify_platform_support_instead_of_an_owner(): void
    {
        Notification::fake();

        [, , $listing] = $this->claimableListing();

        app(CreateBookingInquiry::class)->handle(
            $listing,
            [
                'name' => 'Visitante',
                'email' => 'visitante@example.test',
                'phone' => '3312345678',
                'message' => 'Quiero cotizar un evento.',
                'date_mode' => 'exact',
                'event_date' => now()->addDays(20)->toDateString(),
                'guest_range' => '1-50',
            ],
            null,
            'listing_contact',
        );

        Notification::assertSentOnDemand(
            NewInquiryDirectoryNotification::class,
        );
    }

    /**
     * @return array{0: User, 1: Team, 2: Listing}
     */
    private function claimableListing(): array
    {
        $directory = Team::factory()->directory()->create(['name' => 'Directorio']);
        $listing = Listing::factory()
            ->forTeam($directory)
            ->published()
            ->create([
                'name' => 'Terraza Reclamable',
                'city' => 'Guadalajara',
                'state' => 'Jalisco',
                'contact_name' => 'Dato importado',
                'contact_email' => 'importado@example.test',
                'contact_phone' => '3311111111',
                'claimed_at' => null,
            ]);

        return [User::factory()->create(), $directory, $listing];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function claimPayload(array $overrides = []): array
    {
        return array_merge([
            'business_name' => 'Jardín Los Encinos',
            'role' => 'owner',
            'claimant_name' => 'Ana Pérez',
            'claimant_phone' => '3312345678',
            'whatsapp_phone' => null,
            'target_team_id' => null,
            'message' => 'Soy la dueña y puedo compartir el predial y mi identificación.',
            'accept_terms' => true,
        ], $overrides);
    }

    /**
     * @return array{0: User, 1: Team}
     */
    private function ownerWithTeam(): array
    {
        $owner = User::factory()->create();

        return [$owner, $owner->currentTeam];
    }

    private function superadmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(PlatformAccess::ensure());

        return $user;
    }
}
