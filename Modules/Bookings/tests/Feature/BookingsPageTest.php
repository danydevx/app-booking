<?php

namespace Modules\Bookings\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
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

    public function test_owner_can_render_customers_and_availability_pages(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
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
