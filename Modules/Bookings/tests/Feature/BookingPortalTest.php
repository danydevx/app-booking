<?php

namespace Modules\Bookings\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Bookings\Enums\InquiryStatus;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Notifications\BookingConfirmedByCustomer;
use Modules\Bookings\Notifications\BookingFeedbackReceived;
use Modules\Listings\Models\Listing;
use Tests\TestCase;

class BookingPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_portal_shows_the_booking_and_rejects_invalid_tokens(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $inquiry = BookingInquiry::factory()->forListing($listing)->accepted()->create();

        $this->get(route('public.bookings.portal', ['token' => $inquiry->portal_token]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Bookings/Public/BookingPortal')
                ->where('booking.id', $inquiry->id)
                ->where('booking.can_confirm', true)
                ->where('booking.listing.name', $listing->name));

        $this->get(route('public.bookings.portal', ['token' => 'token-invalido']))
            ->assertNotFound();
    }

    public function test_customer_can_confirm_booking_data(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $inquiry = BookingInquiry::factory()->forListing($listing)->accepted()->create([
            'name' => 'Nombre Anterior',
            'phone' => '5550000000',
        ]);
        $customer = Customer::factory()->create([
            'team_id' => $team->id,
            'email' => $inquiry->email,
        ]);
        $inquiry->forceFill(['customer_id' => $customer->id])->save();

        Notification::fake();

        $this->post(route('public.bookings.portal.confirm', [
            'token' => $inquiry->portal_token,
        ]), [
            'name' => 'Ana Confirmada',
            'phone' => '5551111111',
            'whatsapp_phone' => '5215551111111',
            'guests' => 65,
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $inquiry->refresh();

        $this->assertSame('Ana Confirmada', $inquiry->name);
        $this->assertSame('5551111111', $inquiry->phone);
        $this->assertSame(65, $inquiry->guests);
        $this->assertNotNull($inquiry->customer_confirmed_at);
        $this->assertSame('Ana Confirmada', $customer->fresh()->name);

        $this->assertDatabaseHas('customer_activities', [
            'customer_id' => $customer->id,
            'body' => 'El cliente confirmó los datos de su reserva.',
        ]);

        Notification::assertSentTo($owner, BookingConfirmedByCustomer::class);
    }

    public function test_cancelled_booking_cannot_be_confirmed(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $inquiry = BookingInquiry::factory()->forListing($listing)->create([
            'status' => InquiryStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        $this->post(route('public.bookings.portal.confirm', [
            'token' => $inquiry->portal_token,
        ]), [
            'name' => 'No Permitido',
            'phone' => '5551111111',
        ])->assertSessionHasErrors('booking');

        $this->assertNull($inquiry->fresh()->customer_confirmed_at);
    }

    public function test_customer_can_leave_feedback_after_the_event(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $inquiry = BookingInquiry::factory()->forListing($listing)->accepted()->create([
            'start_date' => now($listing->timezone)->subDays(3)->toDateString(),
            'end_date' => now($listing->timezone)->subDays(2)->toDateString(),
        ]);

        Notification::fake();

        $this->post(route('public.bookings.portal.feedback', [
            'token' => $inquiry->portal_token,
        ]), [
            'rating' => 5,
            'comment' => 'Excelente atención.',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $inquiry->refresh();

        $this->assertSame(5, $inquiry->feedback_rating);
        $this->assertSame('Excelente atención.', $inquiry->feedback_comment);
        $this->assertNotNull($inquiry->feedback_at);

        Notification::assertSentTo($owner, BookingFeedbackReceived::class);
    }

    public function test_feedback_is_rejected_before_the_event_ends(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $inquiry = BookingInquiry::factory()->forListing($listing)->accepted()->create([
            'start_date' => now($listing->timezone)->addDays(3)->toDateString(),
            'end_date' => now($listing->timezone)->addDays(4)->toDateString(),
        ]);

        $this->post(route('public.bookings.portal.feedback', [
            'token' => $inquiry->portal_token,
        ]), [
            'rating' => 5,
        ])->assertSessionHasErrors('feedback');

        $this->assertNull($inquiry->fresh()->feedback_rating);
    }

    public function test_portal_exposes_an_ics_calendar(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $inquiry = BookingInquiry::factory()->forListing($listing)->accepted()->create([
            'start_date' => '2026-12-10',
            'end_date' => '2026-12-11',
        ]);

        $response = $this->get(route('public.bookings.portal.calendar', [
            'token' => $inquiry->portal_token,
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/calendar; charset=UTF-8');
        $content = $response->getContent();

        $this->assertStringContainsString('BEGIN:VCALENDAR', $content);
        $this->assertStringContainsString('DTSTART;VALUE=DATE:20261210', $content);
        $this->assertStringContainsString('DTEND;VALUE=DATE:20261212', $content);
    }

    public function test_owner_booking_detail_includes_portal_and_feedback(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $inquiry = BookingInquiry::factory()->forListing($listing)->accepted()->create([
            'customer_confirmed_at' => now(),
            'feedback_rating' => 4,
            'feedback_comment' => 'Muy bien.',
            'feedback_at' => now(),
        ]);

        $this->actingAs($owner)
            ->get(route('bookings.show', [
                'current_team' => $team->slug,
                'inquiry' => $inquiry->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('inquiry.feedback_rating', 4)
                ->where('inquiry.feedback_comment', 'Muy bien.')
                ->where('inquiry.portal_url', route('public.bookings.portal', [
                    'token' => $inquiry->portal_token,
                ])));
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
