<?php

namespace Modules\Bookings\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Listings\Models\Listing;
use Tests\TestCase;

class BookingHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_accepting_a_request_is_recorded_in_history(): void
    {
        Notification::fake();

        [$owner, $team, $listing] = $this->ownerListing();
        $inquiry = BookingInquiry::factory()->forListing($listing)->create();

        $this->actingAs($owner)
            ->patch(route('bookings.inquiries.accept', [
                'current_team' => $team->slug,
                'inquiry' => $inquiry->id,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'bookings',
            'description' => 'booking.accepted',
            'subject_type' => BookingInquiry::class,
            'subject_id' => $inquiry->id,
        ]);

        $this->actingAs($owner)
            ->get(route('bookings.show', [
                'current_team' => $team->slug,
                'inquiry' => $inquiry->id,
            ]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('activities', 1)
                ->where('activities.0.label', 'Reserva aceptada'));
    }

    public function test_cancelling_a_booking_is_recorded_in_history(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $inquiry = BookingInquiry::factory()->forListing($listing)->accepted()->create();

        $this->actingAs($owner)
            ->patch(route('bookings.inquiries.cancel', [
                'current_team' => $team->slug,
                'inquiry' => $inquiry->id,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'bookings',
            'description' => 'booking.cancelled',
            'subject_type' => BookingInquiry::class,
            'subject_id' => $inquiry->id,
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
