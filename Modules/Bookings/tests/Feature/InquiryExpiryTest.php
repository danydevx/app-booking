<?php

namespace Modules\Bookings\Tests\Feature;

use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Bookings\Enums\InquiryStatus;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Listings\Models\Listing;
use Tests\TestCase;

class InquiryExpiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_expires_new_and_contacted_inquiries_after_the_end_date(): void
    {
        [$listing, $timezone] = $this->listing();

        $new = BookingInquiry::factory()->forListing($listing)->create([
            'status' => InquiryStatus::New,
            'end_date' => now($timezone)->subDay()->toDateString(),
        ]);
        $contacted = BookingInquiry::factory()->forListing($listing)->create([
            'status' => InquiryStatus::Contacted,
            'end_date' => now($timezone)->subDays(2)->toDateString(),
        ]);
        $accepted = BookingInquiry::factory()->forListing($listing)->accepted()->create([
            'end_date' => now($timezone)->subDay()->toDateString(),
        ]);

        $this->artisan('bookings:expire-inquiries')->assertExitCode(0);

        $this->assertSame(InquiryStatus::Expired, $new->fresh()->status);
        $this->assertNotNull($new->fresh()->expired_at);
        $this->assertSame(InquiryStatus::Expired, $contacted->fresh()->status);
        $this->assertSame(InquiryStatus::Accepted, $accepted->fresh()->status);
    }

    public function test_inquiry_ending_today_is_not_expired(): void
    {
        [$listing, $timezone] = $this->listing();

        $inquiry = BookingInquiry::factory()->forListing($listing)->create([
            'status' => InquiryStatus::New,
            'end_date' => now($timezone)->toDateString(),
        ]);

        $this->artisan('bookings:expire-inquiries')->assertExitCode(0);

        $this->assertSame(InquiryStatus::New, $inquiry->fresh()->status);
        $this->assertNull($inquiry->fresh()->expired_at);
    }

    public function test_grace_period_is_respected(): void
    {
        [$listing, $timezone] = $this->listing();
        config(['bookings.expire_after_end_days' => 2]);

        $withinGrace = BookingInquiry::factory()->forListing($listing)->create([
            'status' => InquiryStatus::New,
            'end_date' => now($timezone)->subDay()->toDateString(),
        ]);
        $pastGrace = BookingInquiry::factory()->forListing($listing)->create([
            'status' => InquiryStatus::New,
            'end_date' => now($timezone)->subDays(3)->toDateString(),
        ]);

        $this->artisan('bookings:expire-inquiries')->assertExitCode(0);

        $this->assertSame(InquiryStatus::New, $withinGrace->fresh()->status);
        $this->assertSame(InquiryStatus::Expired, $pastGrace->fresh()->status);
    }

    public function test_dry_run_does_not_update_inquiries(): void
    {
        [$listing, $timezone] = $this->listing();

        $inquiry = BookingInquiry::factory()->forListing($listing)->create([
            'status' => InquiryStatus::New,
            'end_date' => now($timezone)->subDay()->toDateString(),
        ]);

        $this->artisan('bookings:expire-inquiries', ['--dry-run' => true])
            ->expectsOutputToContain('would be expired')
            ->assertExitCode(0);

        $this->assertSame(InquiryStatus::New, $inquiry->fresh()->status);
        $this->assertNull($inquiry->fresh()->expired_at);
    }

    /**
     * @return array{0: Listing, 1: string}
     */
    private function listing(): array
    {
        $team = Team::factory()->create();
        $listing = Listing::factory()->forTeam($team)->published()->create();

        return [$listing, $listing->timezone];
    }
}
