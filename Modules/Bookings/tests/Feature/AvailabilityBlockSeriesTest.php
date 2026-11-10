<?php

namespace Modules\Bookings\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Bookings\Enums\AvailabilityStatus;
use Modules\Bookings\Models\AvailabilityBlockSeries;
use Modules\Bookings\Models\ListingUnavailability;
use Modules\Listings\Models\Listing;
use Tests\TestCase;

class AvailabilityBlockSeriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_a_weekly_recurring_block(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $start = CarbonImmutable::now()->startOfWeek()->addWeek();
        $end = $start->addWeeks(4);

        $this->actingAs($owner)
            ->post(route('bookings.availability.series.store', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]), [
                'reason' => 'Limpieza semanal',
                'recurrence' => 'weekly',
                'weekdays' => [1],
                'starts_on' => $start->toDateString(),
                'ends_on' => $end->toDateString(),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $series = AvailabilityBlockSeries::query()->firstOrFail();

        $this->assertSame('weekly', $series->recurrence->value);
        $this->assertSame(5, ListingUnavailability::query()
            ->where('availability_block_series_id', $series->id)
            ->where('status', AvailabilityStatus::Blocked->value)
            ->count());
    }

    public function test_recurring_block_skips_dates_that_are_already_booked(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $start = CarbonImmutable::now()->startOfWeek()->addWeek();
        $end = $start->addWeeks(2);
        $bookedDate = $start->addWeek()->toDateString();

        ListingUnavailability::factory()->forListing($listing)->booked()->create([
            'date' => $bookedDate,
        ]);

        $this->actingAs($owner)
            ->post(route('bookings.availability.series.store', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]), [
                'reason' => 'Mantenimiento',
                'recurrence' => 'weekly',
                'weekdays' => [1],
                'starts_on' => $start->toDateString(),
                'ends_on' => $end->toDateString(),
            ])
            ->assertSessionHasNoErrors();

        $series = AvailabilityBlockSeries::query()->firstOrFail();

        $this->assertSame(2, ListingUnavailability::query()
            ->where('availability_block_series_id', $series->id)
            ->count());
        $this->assertSame(
            AvailabilityStatus::Booked,
            ListingUnavailability::query()->where('listing_id', $listing->id)->whereDate('date', $bookedDate)->firstOrFail()->status,
        );
    }

    public function test_biweekly_series_uses_every_other_week(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $start = CarbonImmutable::now()->startOfWeek()->addWeek();
        $end = $start->addWeeks(4);

        $this->actingAs($owner)
            ->post(route('bookings.availability.series.store', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]), [
                'reason' => 'Mantenimiento',
                'recurrence' => 'biweekly',
                'weekdays' => [1],
                'starts_on' => $start->toDateString(),
                'ends_on' => $end->toDateString(),
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(3, ListingUnavailability::query()
            ->where('listing_id', $listing->id)
            ->where('status', AvailabilityStatus::Blocked->value)
            ->count());
    }

    public function test_owner_can_delete_a_series_and_release_future_dates(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $start = CarbonImmutable::now()->startOfWeek()->addWeek();
        $end = $start->addWeeks(2);

        $this->actingAs($owner)
            ->post(route('bookings.availability.series.store', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]), [
                'reason' => 'Limpieza',
                'recurrence' => 'weekly',
                'weekdays' => [1],
                'starts_on' => $start->toDateString(),
                'ends_on' => $end->toDateString(),
            ]);

        $series = AvailabilityBlockSeries::query()->firstOrFail();

        $this->actingAs($owner)
            ->delete(route('bookings.availability.series.destroy', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
                'series' => $series->id,
            ]))
            ->assertRedirect();

        $this->assertDatabaseMissing('availability_block_series', ['id' => $series->id]);
        $this->assertDatabaseMissing('listing_unavailability', [
            'availability_block_series_id' => $series->id,
        ]);
    }

    public function test_manual_release_creates_an_exception_for_the_series(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $start = CarbonImmutable::now()->startOfWeek()->addWeek();
        $end = $start->addWeeks(2);

        $this->actingAs($owner)
            ->post(route('bookings.availability.series.store', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]), [
                'reason' => 'Limpieza',
                'recurrence' => 'weekly',
                'weekdays' => [1],
                'starts_on' => $start->toDateString(),
                'ends_on' => $end->toDateString(),
            ]);

        $this->actingAs($owner)
            ->delete(route('bookings.availability.unblock', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
                'date' => $start->toDateString(),
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('availability_block_series_exceptions', [
            'date' => $start->toDateString(),
        ]);
        $this->assertDatabaseMissing('listing_unavailability', [
            'listing_id' => $listing->id,
            'date' => $start->toDateString(),
        ]);
    }

    public function test_series_is_isolated_by_team(): void
    {
        [$owner, $team] = $this->ownerListing();
        $other = User::factory()->create();
        $otherListing = Listing::factory()->forTeam($other->currentTeam)->create();

        $this->actingAs($owner)
            ->post(route('bookings.availability.series.store', [
                'current_team' => $team->slug,
                'listing' => $otherListing->slug,
            ]), [
                'reason' => 'Intento ajeno',
                'recurrence' => 'weekly',
                'weekdays' => [1],
                'starts_on' => CarbonImmutable::now()->addWeek()->toDateString(),
                'ends_on' => CarbonImmutable::now()->addWeeks(2)->toDateString(),
            ])
            ->assertNotFound();
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
