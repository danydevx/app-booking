<?php

namespace Modules\Platform\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Cashier\Subscription;
use Modules\Listings\Enums\ListingStatus;
use Modules\Listings\Models\Listing;
use Modules\Platform\Models\ListingReport;
use Modules\Platform\Notifications\AccountRestored;
use Modules\Platform\Notifications\AccountSuspended;
use Modules\Platform\Support\PlatformAccess;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class PlatformModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_open_all_platform_screens(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $listing = Listing::factory()->forTeam($team)->published()->create();
        $this->subscription($team);
        ListingReport::factory()
            ->forListing($listing)
            ->create();

        $superadmin = $this->superadmin();

        $this->actingAs($superadmin)
            ->get(route('platform.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Platform/Dashboard')
                ->where('platform.isSuperadmin', true)
                ->where('stats.accounts', 2));

        $this->actingAs($superadmin)
            ->get(route('platform.accounts.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Platform/Accounts/Index')
                ->has('accounts.data', 2));

        $this->actingAs($superadmin)
            ->get(route('platform.accounts.show', ['account' => $team->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Platform/Accounts/Show')
                ->where('account.slug', $team->slug)
                ->where('owner.email', $owner->email)
                ->has('listings', 1));

        $this->actingAs($superadmin)
            ->get(route('platform.listings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Platform/Listings/Index')
                ->has('listings.data', 1));

        $this->actingAs($superadmin)
            ->get(route('platform.listings.show', ['listing' => $listing->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Platform/Listings/Show')
                ->where('listing.id', $listing->id)
                ->has('reports', 1));

        $this->actingAs($superadmin)
            ->get(route('platform.subscriptions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Platform/Subscriptions/Index')
                ->has('accounts.data', 1));

        $this->actingAs($superadmin)
            ->get(route('platform.reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Platform/Reports/Index')
                ->has('reports.data', 1));
    }

    public function test_normal_owners_cannot_access_platform_screens_or_actions(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $listing = Listing::factory()->forTeam($team)->published()->create();

        foreach ([
            route('platform.dashboard'),
            route('platform.accounts.index'),
            route('platform.accounts.show', ['account' => $team->slug]),
            route('platform.listings.index'),
            route('platform.listings.show', ['listing' => $listing->slug]),
            route('platform.subscriptions.index'),
            route('platform.reports.index'),
        ] as $url) {
            $this->actingAs($owner)->get($url)->assertForbidden();
        }

        $this->actingAs($owner)
            ->post(route('platform.accounts.suspend', ['account' => $team->slug]), [
                'reason' => 'Intento no autorizado',
            ])
            ->assertForbidden();

        $this->actingAs($owner)
            ->post(route('platform.listings.suspend', ['listing' => $listing->slug]), [
                'reason' => 'Intento no autorizado',
            ])
            ->assertForbidden();

        $this->assertFalse($team->fresh()->isSuspended());
        $this->assertSame(ListingStatus::Published, $listing->fresh()->status);
    }

    public function test_account_suspension_requires_a_reason(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $superadmin = $this->superadmin();

        $this->actingAs($superadmin)
            ->post(route('platform.accounts.suspend', ['account' => $team->slug]))
            ->assertSessionHasErrors('reason');

        $this->assertFalse($team->fresh()->isSuspended());
    }

    public function test_suspending_an_account_hides_listings_and_blocks_member_access(): void
    {
        Notification::fake();

        [$owner, $team] = $this->ownerWithTeam();
        $listing = Listing::factory()->forTeam($team)->published()->create();
        $superadmin = $this->superadmin();

        $this->get(route('public.listings.show', ['listing' => $listing->slug]))
            ->assertOk();

        $this->actingAs($superadmin)
            ->post(route('platform.accounts.suspend', ['account' => $team->slug]), [
                'reason' => 'Actividad sospechosa de cobro',
            ])
            ->assertRedirect();

        $team->refresh();

        $this->assertTrue($team->isSuspended());
        $this->assertSame('Actividad sospechosa de cobro', $team->suspension_reason);

        $this->get(route('public.listings.show', ['listing' => $listing->slug]))
            ->assertNotFound();

        $this->get(route('public.listings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('listings.data', 0));

        $this->actingAs($owner)
            ->get(route('listings.index', ['current_team' => $team->slug]))
            ->assertForbidden();

        Notification::assertSentTo($owner, AccountSuspended::class);

        $activity = Activity::query()
            ->where('description', 'account.suspended')
            ->where('subject_type', Team::class)
            ->where('subject_id', $team->id)
            ->firstOrFail();

        $this->assertSame($superadmin->id, $activity->causer_id);
        $this->assertSame('Actividad sospechosa de cobro', $activity->properties['reason']);
    }

    public function test_restoring_an_account_restores_public_visibility_without_data_loss(): void
    {
        Notification::fake();

        [$owner, $team] = $this->ownerWithTeam();
        $listing = Listing::factory()->forTeam($team)->published()->create();
        $superadmin = $this->superadmin();

        $this->actingAs($superadmin)
            ->post(route('platform.accounts.suspend', ['account' => $team->slug]), [
                'reason' => 'Revisión temporal',
            ])
            ->assertRedirect();

        $this->actingAs($superadmin)
            ->post(route('platform.accounts.restore', ['account' => $team->slug]), [
                'reason' => 'Revisión concluida sin infracción',
            ])
            ->assertRedirect();

        $team->refresh();

        $this->assertFalse($team->isSuspended());
        $this->assertNull($team->suspension_reason);
        $this->assertSame(1, Listing::query()->where('team_id', $team->id)->count());

        $this->get(route('public.listings.show', ['listing' => $listing->slug]))
            ->assertOk();

        $this->actingAs($owner)
            ->get(route('listings.index', ['current_team' => $team->slug]))
            ->assertOk();

        Notification::assertSentTo($owner, AccountRestored::class);

        $this->assertDatabaseHas('activity_log', [
            'description' => 'account.restored',
            'subject_type' => Team::class,
            'subject_id' => $team->id,
            'causer_id' => $superadmin->id,
        ]);
    }

    public function test_listing_suspension_hides_public_url_and_restores_previous_status(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $listing = Listing::factory()->forTeam($team)->published()->create();
        $superadmin = $this->superadmin();

        $this->actingAs($superadmin)
            ->post(route('platform.listings.suspend', ['listing' => $listing->slug]), [
                'reason' => 'Contenido reportado por fraude',
            ])
            ->assertRedirect();

        $listing->refresh();

        $this->assertSame(ListingStatus::Suspended, $listing->status);
        $this->assertSame(ListingStatus::Published, $listing->suspended_from_status);
        $this->assertNotNull($listing->suspended_at);

        $this->get(route('public.listings.show', ['listing' => $listing->slug]))
            ->assertNotFound();

        $this->get(route('public.listings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('listings.data', 0));

        $this->actingAs($owner)
            ->patch(route('listings.publish', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]))
            ->assertForbidden();

        $this->actingAs($owner)
            ->delete(route('listings.destroy', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]))
            ->assertForbidden();

        $this->actingAs($superadmin)
            ->post(route('platform.listings.restore', ['listing' => $listing->slug]), [
                'reason' => 'Reporte descartado tras revisión',
            ])
            ->assertRedirect();

        $listing->refresh();

        $this->assertSame(ListingStatus::Published, $listing->status);
        $this->assertNull($listing->suspended_at);
        $this->assertNull($listing->suspended_from_status);

        $this->get(route('public.listings.show', ['listing' => $listing->slug]))
            ->assertOk();

        $this->assertDatabaseHas('activity_log', [
            'description' => 'listing.suspended',
            'subject_type' => Listing::class,
            'subject_id' => $listing->id,
            'causer_id' => $superadmin->id,
        ]);

        $this->assertDatabaseHas('activity_log', [
            'description' => 'listing.restored',
            'subject_type' => Listing::class,
            'subject_id' => $listing->id,
        ]);
    }

    public function test_restoring_a_draft_listing_returns_it_to_draft(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $listing = Listing::factory()->forTeam($team)->create();
        $superadmin = $this->superadmin();

        $this->actingAs($superadmin)
            ->post(route('platform.listings.suspend', ['listing' => $listing->slug]), [
                'reason' => 'Revisión preventiva',
            ])
            ->assertRedirect();

        $this->actingAs($superadmin)
            ->post(route('platform.listings.restore', ['listing' => $listing->slug]), [
                'reason' => 'Sin hallazgos',
            ])
            ->assertRedirect();

        $this->assertSame(ListingStatus::Draft, $listing->fresh()->status);
    }

    public function test_superadmin_role_grant_and_revoke_are_audited(): void
    {
        $user = User::factory()->create(['email' => 'audit@example.test']);

        $this->artisan('app:make-platform-superadmin', [
            'email' => $user->email,
            '--force' => true,
        ])->assertSuccessful();

        $this->artisan('app:revoke-platform-superadmin', [
            'email' => $user->email,
            '--force' => true,
        ])->assertSuccessful();

        $this->assertFalse($user->fresh()->hasRole(PlatformAccess::ROLE));

        $this->assertDatabaseHas('activity_log', [
            'description' => 'platform.superadmin.granted',
            'subject_type' => User::class,
            'subject_id' => $user->id,
        ]);

        $this->assertDatabaseHas('activity_log', [
            'description' => 'platform.superadmin.revoked',
            'subject_type' => User::class,
            'subject_id' => $user->id,
        ]);
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

    private function subscription(Team $team): Subscription
    {
        return Subscription::query()->create([
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
}
