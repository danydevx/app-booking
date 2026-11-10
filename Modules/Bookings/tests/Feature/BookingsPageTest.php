<?php

namespace Modules\Bookings\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Cashier\Subscription;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Models\Customer;
use Modules\Listings\Models\Listing;
use Tests\TestCase;

class BookingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_render_the_booking_inbox_and_inquiry_detail(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $inquiry = BookingInquiry::factory()->forListing($listing)->create();
        Customer::factory()->create(['team_id' => $team->id]);

        $this->actingAs($owner)
            ->get(route('bookings.index', ['current_team' => $team->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Bookings/Index')
                ->has('inquiries.data', 1)
                ->has('listings', 1));

        $this->actingAs($owner)
            ->get(route('bookings.show', [
                'current_team' => $team->slug,
                'inquiry' => $inquiry->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Bookings/Show')
                ->where('inquiry.id', $inquiry->id));
    }

    public function test_owner_can_render_a_listing_scoped_booking_inbox(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $otherListing = Listing::factory()->forTeam($team)->published()->create();
        $inquiry = BookingInquiry::factory()->forListing($listing)->create();
        BookingInquiry::factory()->forListing($otherListing)->create();

        $this->actingAs($owner)
            ->get(route('bookings.listing', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Bookings/Index')
                ->has('inquiries.data', 1)
                ->where('inquiries.data.0.id', $inquiry->id)
                ->where('activeListing.id', $listing->id)
                ->where('filters.listing', $listing->slug)
                ->where('management.bookings', route('bookings.listing', [
                    'current_team' => $team->slug,
                    'listing' => $listing->slug,
                ])));
    }

    public function test_listing_scoped_inbox_is_isolated_by_team(): void
    {
        [$owner, $team] = $this->ownerListing();
        $foreignTeam = Team::factory()->create();
        $foreignListing = Listing::factory()->forTeam($foreignTeam)->published()->create();

        $this->actingAs($owner)
            ->get(route('bookings.listing', [
                'current_team' => $team->slug,
                'listing' => $foreignListing->slug,
            ]))
            ->assertNotFound();
    }

    public function test_owner_can_render_customers_and_availability_pages(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);

        $this->actingAs($owner)
            ->get(route('bookings.customers.index', ['current_team' => $team->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Bookings/Customers/Index')
                ->has('customers.data', 1));

        $this->actingAs($owner)
            ->get(route('bookings.customers.show', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Bookings/Customers/Show')
                ->where('customer.id', $customer->id));

        $this->actingAs($owner)
            ->get(route('bookings.availability.edit', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Bookings/Availability/Edit')
                ->where('listing.id', $listing->id));
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
}
