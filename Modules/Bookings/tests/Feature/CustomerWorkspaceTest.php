<?php

namespace Modules\Bookings\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Cashier\Subscription;
use Modules\Bookings\Models\Customer;
use Modules\Listings\Models\Listing;
use Tests\TestCase;

class CustomerWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_sections_render_with_the_workspace_submenu(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);

        $this->actingAs($owner)
            ->get(route('bookings.customers.tasks', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Bookings/Customers/Show')
                ->where('section', 'tasks')
                ->where('sectionUrls.summary', route('bookings.customers.show', [
                    'current_team' => $team->slug,
                    'customer' => $customer->id,
                ]))
                ->where('sectionUrls.tasks', route('bookings.customers.tasks', [
                    'current_team' => $team->slug,
                    'customer' => $customer->id,
                ]))
                ->where('sectionUrls.lifecycle', route('bookings.customers.lifecycle', [
                    'current_team' => $team->slug,
                    'customer' => $customer->id,
                ])));
    }

    public function test_scoped_customer_sections_keep_the_listing_context(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $customer = Customer::factory()->create([
            'team_id' => $team->id,
            'listing_id' => $listing->id,
        ]);

        $this->actingAs($owner)
            ->get(route('bookings.listing.customers.timeline', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
                'customer' => $customer->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('section', 'timeline')
                ->where('activeListing.id', $listing->id)
                ->where('sectionUrls.timeline', route('bookings.listing.customers.timeline', [
                    'current_team' => $team->slug,
                    'listing' => $listing->slug,
                    'customer' => $customer->id,
                ])));
    }

    public function test_free_plan_cannot_open_customer_sections(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $customer = Customer::factory()->create(['team_id' => $team->id]);

        $this->actingAs($owner)
            ->get(route('bookings.customers.data', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
            ]))
            ->assertRedirect(route('billing.index', ['current_team' => $team->slug]));
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
    private function ownerListing(): array
    {
        $owner = User::factory()->create();
        $team = $owner->currentTeam;
        $listing = Listing::factory()->forTeam($team)->published()->create();

        return [$owner, $team, $listing];
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
}
