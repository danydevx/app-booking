<?php

namespace Modules\Bookings\Tests\Feature;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Bookings\Enums\AvailabilityStatus;
use Modules\Bookings\Enums\InquiryStatus;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Models\ListingUnavailability;
use Modules\Bookings\Notifications\ManualBookingConfirmation;
use Modules\Listings\Models\Listing;
use Tests\TestCase;

class ManualBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_a_manual_booking_and_block_dates(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $start = now($listing->timezone)->addDays(3);
        $end = $start->copy()->addDays(1);

        $this->actingAs($owner)
            ->post(route('bookings.create.store', ['current_team' => $team->slug]), [
                'listing_id' => $listing->id,
                'name' => 'Cliente Manual',
                'email' => 'reserva@example.test',
                'phone' => '5555555555',
                'whatsapp_phone' => '5215555555555',
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'event_type' => 'Cumpleaños',
                'guests' => 20,
                'message' => 'Reserva tomada por teléfono.',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $inquiry = BookingInquiry::query()->firstOrFail();
        $customer = Customer::query()->firstOrFail();

        $this->assertSame(InquiryStatus::Accepted, $inquiry->status);
        $this->assertSame('manual', $inquiry->source);
        $this->assertSame($team->id, $inquiry->team_id);
        $this->assertSame($customer->id, $inquiry->customer_id);
        $this->assertSame('reserva@example.test', $customer->email);
        $this->assertSame(2, ListingUnavailability::query()
            ->where('booking_inquiry_id', $inquiry->id)
            ->where('status', AvailabilityStatus::Booked)
            ->count());
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'bookings',
            'description' => 'booking.created_manual',
            'subject_type' => BookingInquiry::class,
            'subject_id' => $inquiry->id,
        ]);
    }

    public function test_manual_booking_sends_confirmation_to_customer(): void
    {
        Notification::fake();

        [$owner, $team, $listing] = $this->ownerListing();

        $this->actingAs($owner)
            ->post(route('bookings.create.store', ['current_team' => $team->slug]), [
                'listing_id' => $listing->id,
                'name' => 'Cliente Manual',
                'email' => 'confirmacion@example.test',
                'phone' => '5555555555',
                'start_date' => now($listing->timezone)->addDays(3)->toDateString(),
                'end_date' => now($listing->timezone)->addDays(3)->toDateString(),
                'notify_customer' => true,
            ])
            ->assertSessionHasNoErrors();

        Notification::assertSentOnDemand(
            ManualBookingConfirmation::class,
            fn (ManualBookingConfirmation $notification, array $channels, AnonymousNotifiable $notifiable): bool => ($notifiable->routes['mail'] ?? null) === 'confirmacion@example.test',
        );
    }

    public function test_manual_booking_can_skip_customer_confirmation(): void
    {
        Notification::fake();

        [$owner, $team, $listing] = $this->ownerListing();

        $this->actingAs($owner)
            ->post(route('bookings.create.store', ['current_team' => $team->slug]), [
                'listing_id' => $listing->id,
                'name' => 'Cliente Manual',
                'email' => 'sin-correo@example.test',
                'phone' => '5555555555',
                'start_date' => now($listing->timezone)->addDays(3)->toDateString(),
                'end_date' => now($listing->timezone)->addDays(3)->toDateString(),
                'notify_customer' => false,
            ])
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_manual_booking_reuses_an_existing_customer(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $customer = Customer::factory()->create([
            'team_id' => $team->id,
            'email' => 'cliente@example.test',
            'phone' => null,
        ]);

        $this->actingAs($owner)
            ->post(route('bookings.create.store', ['current_team' => $team->slug]), [
                'listing_id' => $listing->id,
                'name' => 'Cliente Existente',
                'email' => 'cliente@example.test',
                'phone' => '5550000000',
                'start_date' => now($listing->timezone)->addDays(4)->toDateString(),
                'end_date' => now($listing->timezone)->addDays(4)->toDateString(),
            ])
            ->assertSessionHasNoErrors();

        $inquiry = BookingInquiry::query()->firstOrFail();

        $this->assertSame($customer->id, $inquiry->customer_id);
        $this->assertSame(1, Customer::query()->count());
        $this->assertSame('5550000000', $customer->fresh()->phone);
    }

    public function test_manual_booking_rejects_unavailable_dates(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $date = now($listing->timezone)->addDays(3)->toDateString();

        ListingUnavailability::factory()->forListing($listing)->create([
            'date' => $date,
            'status' => AvailabilityStatus::Blocked,
        ]);

        $this->actingAs($owner)
            ->post(route('bookings.create.store', ['current_team' => $team->slug]), [
                'listing_id' => $listing->id,
                'name' => 'Cliente Manual',
                'email' => 'conflicto@example.test',
                'phone' => '5555555555',
                'start_date' => $date,
                'end_date' => $date,
            ])
            ->assertSessionHasErrors('dates');

        $this->assertDatabaseCount('booking_inquiries', 0);
        $this->assertDatabaseCount('customers', 0);
    }

    public function test_member_cannot_create_a_manual_booking(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $member = User::factory()->create();
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);

        $this->actingAs($member)
            ->get(route('bookings.create', ['current_team' => $team->slug]))
            ->assertForbidden();

        $this->actingAs($member)
            ->post(route('bookings.create.store', ['current_team' => $team->slug]), [
                'listing_id' => $listing->id,
                'name' => 'Cliente Manual',
                'email' => 'miembro@example.test',
                'phone' => '5555555555',
                'start_date' => now($listing->timezone)->addDays(3)->toDateString(),
                'end_date' => now($listing->timezone)->addDays(3)->toDateString(),
            ])
            ->assertForbidden();
    }

    public function test_owner_cannot_use_a_listing_from_another_team(): void
    {
        [$owner, $team] = $this->ownerListing();
        $otherTeam = Team::factory()->create();
        $otherListing = Listing::factory()->forTeam($otherTeam)->published()->create();

        $this->actingAs($owner)
            ->post(route('bookings.create.store', ['current_team' => $team->slug]), [
                'listing_id' => $otherListing->id,
                'name' => 'Cliente Manual',
                'email' => 'ajeno@example.test',
                'phone' => '5555555555',
                'start_date' => now($otherListing->timezone)->addDays(3)->toDateString(),
                'end_date' => now($otherListing->timezone)->addDays(3)->toDateString(),
            ])
            ->assertSessionHasErrors('listing_id');
    }

    public function test_owner_can_open_the_manual_booking_form(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();

        $this->actingAs($owner)
            ->get(route('bookings.create', ['current_team' => $team->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Bookings/Create')
                ->has('listings', 1)
                ->where('listings.0.id', $listing->id));
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
