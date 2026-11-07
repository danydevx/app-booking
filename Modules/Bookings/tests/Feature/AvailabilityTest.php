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

    public function test_owner_can_block_a_range_of_dates(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $timezone = $listing->timezone;
        $start = now($timezone)->addDays(5);
        $end = now($timezone)->addDays(7);

        $this->actingAs($owner)->post(
            route('bookings.availability.block-range', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]),
            [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'reason' => 'Vacaciones',
            ],
        )->assertRedirect();

        $this->assertSame(3, ListingUnavailability::query()
            ->where('listing_id', $listing->id)
            ->where('status', AvailabilityStatus::Blocked->value)
            ->count());

        $this->assertDatabaseHas('listing_unavailability', [
            'listing_id' => $listing->id,
            'date' => $start->toDateString(),
            'status' => AvailabilityStatus::Blocked->value,
            'reason' => 'Vacaciones',
        ]);
        $this->assertDatabaseHas('listing_unavailability', [
            'listing_id' => $listing->id,
            'date' => $end->toDateString(),
            'status' => AvailabilityStatus::Blocked->value,
        ]);
    }

    public function test_range_block_fails_without_partial_changes_when_a_date_is_booked(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $timezone = $listing->timezone;
        $booked = now($timezone)->addDays(6);

        ListingUnavailability::factory()->forListing($listing)->booked()->create([
            'date' => $booked->toDateString(),
        ]);

        $this->actingAs($owner)->post(
            route('bookings.availability.block-range', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]),
            [
                'start_date' => now($timezone)->addDays(5)->toDateString(),
                'end_date' => now($timezone)->addDays(7)->toDateString(),
            ],
        )->assertSessionHasErrors('start_date');

        $this->assertSame(1, ListingUnavailability::query()
            ->where('listing_id', $listing->id)
            ->count());
    }

    public function test_range_block_updates_existing_blocked_dates(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $timezone = $listing->timezone;
        $middle = now($timezone)->addDays(6);

        ListingUnavailability::factory()->forListing($listing)->create([
            'date' => $middle->toDateString(),
            'status' => AvailabilityStatus::Blocked,
            'reason' => 'Anterior',
        ]);

        $this->actingAs($owner)->post(
            route('bookings.availability.block-range', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]),
            [
                'start_date' => now($timezone)->addDays(5)->toDateString(),
                'end_date' => now($timezone)->addDays(7)->toDateString(),
                'reason' => 'Nuevo motivo',
            ],
        )->assertRedirect();

        $this->assertSame(3, ListingUnavailability::query()
            ->where('listing_id', $listing->id)
            ->count());

        $this->assertDatabaseHas('listing_unavailability', [
            'listing_id' => $listing->id,
            'date' => $middle->toDateString(),
            'reason' => 'Nuevo motivo',
        ]);
    }

    public function test_range_block_validates_dates_and_max_days(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $timezone = $listing->timezone;

        $this->actingAs($owner)->post(
            route('bookings.availability.block-range', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]),
            [
                'start_date' => now($timezone)->addDays(5)->toDateString(),
                'end_date' => now($timezone)->addDays(1)->toDateString(),
            ],
        )->assertSessionHasErrors('end_date');

        $this->actingAs($owner)->post(
            route('bookings.availability.block-range', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]),
            [
                'start_date' => now($timezone)->subDay()->toDateString(),
                'end_date' => now($timezone)->addDay()->toDateString(),
            ],
        )->assertSessionHasErrors('start_date');

        config(['bookings.max_block_days' => 2]);

        $this->actingAs($owner)->post(
            route('bookings.availability.block-range', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]),
            [
                'start_date' => now($timezone)->addDays(5)->toDateString(),
                'end_date' => now($timezone)->addDays(8)->toDateString(),
            ],
        )->assertSessionHasErrors('end_date');

        $this->assertSame(0, ListingUnavailability::query()->count());
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
