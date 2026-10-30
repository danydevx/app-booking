<?php

namespace Modules\Bookings\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Bookings\Enums\AvailabilityStatus;
use Modules\Bookings\Models\ListingUnavailability;
use Modules\Listings\Models\Listing;
use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_block_and_unblock_a_future_date(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $date = now($listing->timezone)->addDays(5)->toDateString();

        $this->actingAs($owner)->post(
            route('bookings.availability.block', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]),
            ['date' => $date, 'reason' => 'Mantenimiento'],
        )->assertRedirect();

        $this->assertDatabaseHas('listing_unavailability', [
            'listing_id' => $listing->id,
            'date' => $date,
            'status' => AvailabilityStatus::Blocked->value,
            'reason' => 'Mantenimiento',
        ]);

        $this->actingAs($owner)->delete(
            route('bookings.availability.unblock', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
                'date' => $date,
            ]),
        )->assertRedirect();

        $this->assertDatabaseMissing('listing_unavailability', [
            'listing_id' => $listing->id,
            'date' => $date,
        ]);
    }

    public function test_a_booked_date_cannot_be_blocked_or_unblocked_manually(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $date = now($listing->timezone)->addDays(5)->toDateString();

        ListingUnavailability::factory()->forListing($listing)->booked()->create([
            'date' => $date,
        ]);

        $this->actingAs($owner)->post(
            route('bookings.availability.block', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]),
            ['date' => $date],
        )->assertSessionHasErrors('date');

        $this->actingAs($owner)->delete(
            route('bookings.availability.unblock', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
                'date' => $date,
            ]),
        )->assertSessionHasErrors('date');
    }

    public function test_public_availability_page_exposes_blocked_dates(): void
    {
        $team = Team::factory()->create();
        $listing = Listing::factory()->forTeam($team)->published()->create();
        $date = now($listing->timezone)->addDays(7)->toDateString();

        ListingUnavailability::factory()->forListing($listing)->create([
            'date' => $date,
            'status' => AvailabilityStatus::Blocked,
        ]);

        $this->get(route('public.bookings.availability', ['listing' => $listing->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Bookings/Public/Availability')
                ->where('blocked_dates.0.date', $date)
                ->where('blocked_dates.0.status', AvailabilityStatus::Blocked->value));
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
