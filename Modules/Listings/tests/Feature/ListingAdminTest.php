<?php

namespace Modules\Listings\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Cashier\Subscription;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Models\Customer;
use Modules\Listings\Models\Listing;
use Tests\TestCase;

class ListingAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_open_the_terrace_administration_hub(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $customer = Customer::factory()->create(['team_id' => $team->id]);

        BookingInquiry::factory()
            ->forListing($listing)
            ->forCustomer($customer)
            ->accepted()
            ->create([
                'start_date' => now($listing->timezone)->addDays(3)->toDateString(),
                'end_date' => now($listing->timezone)->addDays(4)->toDateString(),
            ]);

        BookingInquiry::factory()
            ->forListing($listing)
            ->forCustomer($customer)
            ->create();

        $this->actingAs($owner)
            ->get(route('listings.manage', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listings/Manage')
                ->where('stats.inquiries', 2)
                ->where('stats.new_inquiries', 1)
                ->where('stats.accepted_inquiries', 1)
                ->where('stats.upcoming_reservations', 1)
                ->where('stats.customers', 1)
                ->has('upcomingReservations', 1)
                ->has('recentInquiries', 2)
                ->has('customers', 1)
                ->where('listing.id', $listing->id));
    }

    public function test_customers_index_can_be_filtered_by_listing(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $otherListing = Listing::factory()->forTeam($team)->published()->create();
        $this->subscribePro($team);

        $customerA = Customer::factory()->create(['team_id' => $team->id]);
        $customerB = Customer::factory()->create(['team_id' => $team->id]);

        BookingInquiry::factory()->forListing($listing)->forCustomer($customerA)->create();
        BookingInquiry::factory()->forListing($otherListing)->forCustomer($customerB)->create();

        $this->actingAs($owner)
            ->get(route('bookings.customers.index', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('customers.data', 1)
                ->where('customers.data.0.id', $customerA->id));
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
