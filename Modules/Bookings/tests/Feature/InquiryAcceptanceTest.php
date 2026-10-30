<?php

namespace Modules\Bookings\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Bookings\Enums\AvailabilityStatus;
use Modules\Bookings\Enums\InquiryStatus;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Models\ListingUnavailability;
use Modules\Listings\Models\Listing;
use Tests\TestCase;

class InquiryAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_accepts_an_inquiry_and_books_inclusive_dates(): void
    {
        Notification::fake();

        [$owner, $team, $listing] = $this->ownerListing();
        $start = now()->addDays(10)->toDateString();
        $end = now()->addDays(12)->toDateString();
        $inquiry = BookingInquiry::factory()->forListing($listing)->create([
            'start_date' => $start,
            'end_date' => $end,
        ]);

        $this->actingAs($owner)->patch($this->acceptUrl($team, $inquiry))->assertRedirect();

        $inquiry->refresh();

        $this->assertSame(InquiryStatus::Accepted, $inquiry->status);
        $this->assertNotNull($inquiry->accepted_at);
        $this->assertSame(3, ListingUnavailability::query()->where('booking_inquiry_id', $inquiry->id)->count());
        $this->assertDatabaseHas('listing_unavailability', [
            'booking_inquiry_id' => $inquiry->id,
            'date' => $start,
            'status' => AvailabilityStatus::Booked->value,
        ]);
        $this->assertDatabaseHas('listing_unavailability', [
            'booking_inquiry_id' => $inquiry->id,
            'date' => $end,
            'status' => AvailabilityStatus::Booked->value,
        ]);
    }

    public function test_accepting_the_same_inquiry_is_idempotent(): void
    {
        Notification::fake();

        [$owner, $team, $listing] = $this->ownerListing();
        $inquiry = BookingInquiry::factory()->forListing($listing)->create([
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(11)->toDateString(),
        ]);

        $this->actingAs($owner)->patch($this->acceptUrl($team, $inquiry))->assertRedirect();
        $this->actingAs($owner)->patch($this->acceptUrl($team, $inquiry))->assertRedirect();

        $this->assertSame(2, ListingUnavailability::query()->where('booking_inquiry_id', $inquiry->id)->count());
    }

    public function test_acceptance_fails_when_a_requested_date_is_blocked(): void
    {
        Notification::fake();

        [$owner, $team, $listing] = $this->ownerListing();
        $date = now()->addDays(10)->toDateString();
        $inquiry = BookingInquiry::factory()->forListing($listing)->create([
            'start_date' => $date,
            'end_date' => now()->addDays(12)->toDateString(),
        ]);

        ListingUnavailability::factory()->forListing($listing)->create([
            'date' => $date,
            'status' => AvailabilityStatus::Blocked,
        ]);

        $this->actingAs($owner)->patch($this->acceptUrl($team, $inquiry))
            ->assertSessionHasErrors('dates');

        $this->assertSame(InquiryStatus::New, $inquiry->fresh()->status);
        $this->assertDatabaseMissing('listing_unavailability', [
            'booking_inquiry_id' => $inquiry->id,
        ]);
    }

    public function test_two_overlapping_inquiries_cannot_both_be_accepted(): void
    {
        Notification::fake();

        [$owner, $team, $listing] = $this->ownerListing();
        $start = now()->addDays(10)->toDateString();
        $end = now()->addDays(12)->toDateString();
        $first = BookingInquiry::factory()->forListing($listing)->create([
            'start_date' => $start,
            'end_date' => $end,
        ]);
        $second = BookingInquiry::factory()->forListing($listing)->create([
            'start_date' => $start,
            'end_date' => $end,
        ]);

        $this->actingAs($owner)->patch($this->acceptUrl($team, $first))->assertRedirect();
        $this->actingAs($owner)->patch($this->acceptUrl($team, $second))
            ->assertSessionHasErrors('dates');

        $this->assertSame(InquiryStatus::Accepted, $first->fresh()->status);
        $this->assertSame(InquiryStatus::New, $second->fresh()->status);
    }

    public function test_declining_an_accepted_inquiry_releases_its_dates(): void
    {
        Notification::fake();

        [$owner, $team, $listing] = $this->ownerListing();
        $inquiry = BookingInquiry::factory()->forListing($listing)->create([
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(11)->toDateString(),
        ]);

        $this->actingAs($owner)->patch($this->acceptUrl($team, $inquiry))->assertRedirect();
        $this->actingAs($owner)->patch(route('bookings.inquiries.decline', [
            'current_team' => $team->slug,
            'inquiry' => $inquiry->id,
        ]))->assertRedirect();

        $this->assertSame(InquiryStatus::Declined, $inquiry->fresh()->status);
        $this->assertDatabaseMissing('listing_unavailability', [
            'booking_inquiry_id' => $inquiry->id,
        ]);
    }

    public function test_cancelling_an_accepted_inquiry_releases_its_dates(): void
    {
        Notification::fake();

        [$owner, $team, $listing] = $this->ownerListing();
        $inquiry = BookingInquiry::factory()->forListing($listing)->create([
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(11)->toDateString(),
        ]);

        $this->actingAs($owner)->patch($this->acceptUrl($team, $inquiry))->assertRedirect();
        $this->actingAs($owner)->patch(route('bookings.inquiries.cancel', [
            'current_team' => $team->slug,
            'inquiry' => $inquiry->id,
        ]))->assertRedirect();

        $this->assertSame(InquiryStatus::Cancelled, $inquiry->fresh()->status);
        $this->assertDatabaseMissing('listing_unavailability', [
            'booking_inquiry_id' => $inquiry->id,
        ]);
    }

    public function test_inquiry_from_another_account_cannot_be_mutated(): void
    {
        [$owner, $team] = $this->ownerListing();
        $otherTeam = Team::factory()->create();
        $otherListing = Listing::factory()->forTeam($otherTeam)->create();
        $foreignInquiry = BookingInquiry::factory()->forListing($otherListing)->create();

        $this->actingAs($owner)->patch(route('bookings.inquiries.accept', [
            'current_team' => $team->slug,
            'inquiry' => $foreignInquiry->id,
        ]))->assertNotFound();

        $this->assertSame(InquiryStatus::New, $foreignInquiry->fresh()->status);
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

    private function acceptUrl(Team $team, BookingInquiry $inquiry): string
    {
        return route('bookings.inquiries.accept', [
            'current_team' => $team->slug,
            'inquiry' => $inquiry->id,
        ]);
    }
}
