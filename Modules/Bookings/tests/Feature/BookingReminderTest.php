<?php

namespace Modules\Bookings\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Notifications\UpcomingBookingReminder;
use Modules\Listings\Models\Listing;
use Tests\TestCase;

class BookingReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_receives_one_reminder_for_a_booking_starting_soon(): void
    {
        Notification::fake();

        [$owner, $listing] = $this->ownerListing();
        $timezone = $listing->timezone;
        $start = now($timezone)->addDays(3);

        $inquiry = BookingInquiry::factory()->forListing($listing)->accepted()->create([
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addDay()->toDateString(),
        ]);

        $this->artisan('bookings:send-reminders')->assertExitCode(0);

        Notification::assertSentTo($owner, UpcomingBookingReminder::class);
        $this->assertNotNull($inquiry->fresh()->reminder_sent_at);

        $this->artisan('bookings:send-reminders')->assertExitCode(0);

        Notification::assertSentToTimes($owner, UpcomingBookingReminder::class, 1);
    }

    public function test_non_accepted_or_distant_bookings_are_not_reminded(): void
    {
        Notification::fake();

        [$owner, $listing] = $this->ownerListing();
        $timezone = $listing->timezone;

        BookingInquiry::factory()->forListing($listing)->create([
            'start_date' => now($timezone)->addDays(3)->toDateString(),
            'end_date' => now($timezone)->addDays(4)->toDateString(),
        ]);

        BookingInquiry::factory()->forListing($listing)->accepted()->create([
            'start_date' => now($timezone)->addDays(10)->toDateString(),
            'end_date' => now($timezone)->addDays(11)->toDateString(),
        ]);

        $this->artisan('bookings:send-reminders')->assertExitCode(0);

        Notification::assertNothingSent();
    }

    public function test_dry_run_does_not_send_or_mark_reminders(): void
    {
        Notification::fake();

        [$owner, $listing] = $this->ownerListing();
        $timezone = $listing->timezone;
        $start = now($timezone)->addDays(3);

        $inquiry = BookingInquiry::factory()->forListing($listing)->accepted()->create([
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addDay()->toDateString(),
        ]);

        $this->artisan('bookings:send-reminders', ['--dry-run' => true])
            ->expectsOutputToContain('would be sent')
            ->assertExitCode(0);

        Notification::assertNothingSent();
        $this->assertNull($inquiry->fresh()->reminder_sent_at);
    }

    /**
     * @return array{0: User, 1: Listing}
     */
    private function ownerListing(): array
    {
        $owner = User::factory()->create();
        $team = $owner->currentTeam;
        $listing = Listing::factory()->forTeam($team)->published()->create();

        return [$owner, $listing];
    }
}
