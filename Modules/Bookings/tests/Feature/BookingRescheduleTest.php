<?php

namespace Modules\Bookings\Tests\Feature;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Bookings\Enums\AvailabilityStatus;
use Modules\Bookings\Enums\InquiryStatus;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Models\ListingUnavailability;
use Modules\Bookings\Notifications\BookingRescheduledConfirmation;
use Modules\Listings\Models\Listing;
use Tests\TestCase;

class BookingRescheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_reschedule_an_accepted_booking(): void
    {
        Notification::fake();

        [$owner, $team, $listing] = $this->ownerListing();
        $customer = Customer::factory()->create([
            'team_id' => $team->id,
            'name' => 'Cliente Original',
            'email' => 'cliente@example.test',
        ]);
        $oldStart = now($listing->timezone)->addDays(5);
        $inquiry = BookingInquiry::factory()
            ->forListing($listing)
            ->forCustomer($customer)
            ->accepted()
            ->create([
                'name' => 'Cliente Original',
                'email' => 'cliente@example.test',
                'phone' => '5550000000',
                'start_date' => $oldStart->toDateString(),
                'end_date' => $oldStart->copy()->addDay()->toDateString(),
                'reminder_sent_at' => now(),
            ]);

        $this->bookDates($inquiry, $oldStart->toDateString(), $oldStart->copy()->addDay()->toDateString());

        $newStart = now($listing->timezone)->addDays(10);
        $newEnd = $newStart->copy()->addDay();

        $this->actingAs($owner)
            ->patch(route('bookings.inquiries.update', [
                'current_team' => $team->slug,
                'inquiry' => $inquiry->id,
            ]), $this->payload($inquiry, [
                'start_date' => $newStart->toDateString(),
                'end_date' => $newEnd->toDateString(),
                'name' => 'Cliente Actualizado',
                'phone' => '5551111111',
                'notify_customer' => true,
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('bookings.show', [
                'current_team' => $team->slug,
                'inquiry' => $inquiry->id,
            ]));

        $inquiry->refresh();

        $this->assertSame($newStart->toDateString(), $inquiry->start_date->toDateString());
        $this->assertSame($newEnd->toDateString(), $inquiry->end_date->toDateString());
        $this->assertNull($inquiry->reminder_sent_at);
        $this->assertSame('Cliente Actualizado', $customer->fresh()->name);
        $this->assertSame('5551111111', $customer->fresh()->phone);
        $this->assertSame(0, ListingUnavailability::query()
            ->where('booking_inquiry_id', $inquiry->id)
            ->whereIn('date', [$oldStart->toDateString(), $oldStart->copy()->addDay()->toDateString()])
            ->count());
        $this->assertSame(2, ListingUnavailability::query()
            ->where('booking_inquiry_id', $inquiry->id)
            ->whereIn('date', [$newStart->toDateString(), $newEnd->toDateString()])
            ->count());

        Notification::assertSentOnDemand(
            BookingRescheduledConfirmation::class,
            fn (BookingRescheduledConfirmation $notification, array $channels, AnonymousNotifiable $notifiable): bool => ($notifiable->routes['mail'] ?? null) === 'cliente@example.test',
        );
    }

    public function test_reschedule_rejects_conflicting_dates_and_keeps_old_ones(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $oldStart = now($listing->timezone)->addDays(5);
        $inquiry = BookingInquiry::factory()
            ->forListing($listing)
            ->accepted()
            ->create([
                'start_date' => $oldStart->toDateString(),
                'end_date' => $oldStart->copy()->addDay()->toDateString(),
            ]);
        $this->bookDates($inquiry, $oldStart->toDateString(), $oldStart->copy()->addDay()->toDateString());

        $blocked = now($listing->timezone)->addDays(12)->toDateString();
        ListingUnavailability::factory()->forListing($listing)->create([
            'date' => $blocked,
            'status' => AvailabilityStatus::Blocked,
        ]);

        $this->actingAs($owner)
            ->patch(route('bookings.inquiries.update', [
                'current_team' => $team->slug,
                'inquiry' => $inquiry->id,
            ]), $this->payload($inquiry, [
                'start_date' => $blocked,
                'end_date' => $blocked,
            ]))
            ->assertSessionHasErrors('dates');

        $this->assertSame(2, ListingUnavailability::query()
            ->where('booking_inquiry_id', $inquiry->id)
            ->count());
    }

    public function test_pending_inquiry_can_change_dates_without_blocking_them(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $inquiry = BookingInquiry::factory()->forListing($listing)->create([
            'status' => InquiryStatus::Contacted,
            'start_date' => now($listing->timezone)->addDays(5)->toDateString(),
            'end_date' => now($listing->timezone)->addDays(5)->toDateString(),
        ]);

        $this->actingAs($owner)
            ->patch(route('bookings.inquiries.update', [
                'current_team' => $team->slug,
                'inquiry' => $inquiry->id,
            ]), $this->payload($inquiry, [
                'start_date' => now($listing->timezone)->addDays(7)->toDateString(),
                'end_date' => now($listing->timezone)->addDays(7)->toDateString(),
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('listing_unavailability', 0);
        $this->assertSame(InquiryStatus::Contacted, $inquiry->fresh()->status);
    }

    public function test_member_cannot_edit_a_booking(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $member = User::factory()->create();
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);
        $inquiry = BookingInquiry::factory()->forListing($listing)->accepted()->create();

        $this->actingAs($member)
            ->get(route('bookings.inquiries.edit', [
                'current_team' => $team->slug,
                'inquiry' => $inquiry->id,
            ]))
            ->assertForbidden();
    }

    public function test_terminal_inquiry_cannot_be_edited(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $inquiry = BookingInquiry::factory()->forListing($listing)->create([
            'status' => InquiryStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        $this->actingAs($owner)
            ->patch(route('bookings.inquiries.update', [
                'current_team' => $team->slug,
                'inquiry' => $inquiry->id,
            ]), $this->payload($inquiry))
            ->assertSessionHasErrors('status');
    }

    public function test_owner_can_open_the_edit_form(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $inquiry = BookingInquiry::factory()->forListing($listing)->accepted()->create();

        $this->actingAs($owner)
            ->get(route('bookings.inquiries.edit', [
                'current_team' => $team->slug,
                'inquiry' => $inquiry->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Bookings/Edit')
                ->where('inquiry.id', $inquiry->id));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(BookingInquiry $inquiry, array $overrides = []): array
    {
        return [
            'name' => $inquiry->name,
            'email' => $inquiry->email,
            'phone' => $inquiry->phone,
            'whatsapp_phone' => $inquiry->whatsapp_phone,
            'start_date' => $inquiry->start_date->toDateString(),
            'end_date' => $inquiry->end_date->toDateString(),
            'event_type' => $inquiry->event_type,
            'guests' => $inquiry->guests,
            'message' => $inquiry->message,
            'notify_customer' => false,
            ...$overrides,
        ];
    }

    private function bookDates(BookingInquiry $inquiry, string $start, string $end): void
    {
        foreach (range(
            CarbonImmutable::parse($start)->timestamp,
            CarbonImmutable::parse($end)->timestamp,
            86400,
        ) as $timestamp) {
            ListingUnavailability::factory()->forListing($inquiry->listing)->booked()->create([
                'date' => CarbonImmutable::createFromTimestamp($timestamp)->toDateString(),
                'booking_inquiry_id' => $inquiry->id,
            ]);
        }
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
