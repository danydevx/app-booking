<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Cashier\Subscription;
use Modules\Bookings\Enums\ContractStatus;
use Modules\Bookings\Enums\QuoteStatus;
use Modules\Bookings\Models\BookingContract;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Models\CustomerQuote;
use Modules\Bookings\Notifications\BookingConfirmedByCustomer;
use Modules\Bookings\Notifications\BookingContractAccepted;
use Modules\Bookings\Notifications\BookingFeedbackReceived;
use Modules\Bookings\Notifications\CustomerQuoteSent;
use Modules\Listings\Models\Listing;
use Tests\TestCase;

class FullFlowSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_and_admin_full_booking_flow(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $team = $owner->currentTeam;
        $listing = Listing::factory()->forTeam($team)->published()->create();
        $this->subscribePro($team);

        // Public landing and marketplace.
        $this->get(route('home'))->assertOk();
        $this->get(route('public.listings.index'))->assertOk();
        $this->get(route('public.listings.show', ['listing' => $listing->slug]))->assertOk();
        $this->get(route('public.bookings.availability', ['listing' => $listing->slug]))->assertOk();

        // Public inquiry.
        $start = now($listing->timezone)->addDays(12)->startOfDay();

        $this->post(route('public.bookings.inquiries.store', ['listing' => $listing->slug]), [
            'name' => 'Cliente Smoke Test',
            'email' => 'smoke@example.test',
            'phone' => '5551234567',
            'whatsapp_phone' => '5215551234567',
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addDay()->toDateString(),
            'event_type' => 'Cumpleaños',
            'guests' => 45,
            'message' => 'Solicitud generada por el smoke test.',
            'consent' => true,
            'website' => '',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('public.bookings.availability', ['listing' => $listing->slug]));

        $inquiry = BookingInquiry::query()->firstOrFail();
        $customer = Customer::query()->where('email', 'smoke@example.test')->firstOrFail();

        $this->assertSame($team->id, $inquiry->team_id);
        $this->assertSame($customer->id, $inquiry->customer_id);

        // Admin area under /app.
        $this->actingAs($owner);

        $this->get(route('dashboard', ['current_team' => $team->slug]))->assertOk();
        $this->get(route('bookings.index', ['current_team' => $team->slug]))->assertOk();
        $this->get(route('bookings.listing', [
            'current_team' => $team->slug,
            'listing' => $listing->slug,
        ]))->assertOk();
        $this->get(route('bookings.customers.index', ['current_team' => $team->slug]))->assertOk();
        $this->get(route('bookings.listing.customers', [
            'current_team' => $team->slug,
            'listing' => $listing->slug,
        ]))->assertOk();
        $this->get(route('bookings.today', ['current_team' => $team->slug]))->assertOk();
        $this->get(route('bookings.reports', ['current_team' => $team->slug]))->assertOk();

        // Customer workspace sections.
        foreach ([
            'bookings.customers.show',
            'bookings.customers.tasks',
            'bookings.customers.timeline',
            'bookings.customers.emails',
            'bookings.customers.quotes',
            'bookings.customers.inquiries',
            'bookings.customers.data',
            'bookings.customers.tags',
            'bookings.customers.lifecycle',
        ] as $routeName) {
            $this->get(route($routeName, [
                'current_team' => $team->slug,
                'customer' => $customer->id,
            ]))->assertOk();
        }

        // Quote creation and public acceptance.
        $quoteStart = now($listing->timezone)->addDays(20)->startOfDay();

        $this->post(route('bookings.customers.quotes.store', [
            'current_team' => $team->slug,
            'customer' => $customer->id,
        ]), [
            'listing_id' => $listing->id,
            'start_date' => $quoteStart->toDateString(),
            'end_date' => $quoteStart->copy()->addDay()->toDateString(),
            'event_type' => 'Cumpleaños',
            'guests' => 45,
            'subtotal' => '8500.00',
            'deposit' => '2000.00',
            'notes' => 'Incluye mesas y sillas.',
            'terms' => 'Anticipo no reembolsable.',
            'expires_at' => now()->addDays(5)->toDateTimeString(),
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $quote = CustomerQuote::query()->firstOrFail();

        $this->post(route('bookings.quotes.send', [
            'current_team' => $team->slug,
            'quote' => $quote->id,
        ]))->assertSessionHasNoErrors();

        $quote->refresh();

        $this->assertSame(QuoteStatus::Sent, $quote->status);
        Notification::assertSentOnDemand(CustomerQuoteSent::class);

        $this->get(route('public.quotes.show', ['token' => $quote->public_token]))->assertOk();
        $this->post(route('public.quotes.accept', ['token' => $quote->public_token]))
            ->assertSessionHasNoErrors();

        $quote->refresh();

        $this->assertSame(QuoteStatus::Accepted, $quote->status);

        // Convert quote to booking.
        $this->post(route('bookings.quotes.convert', [
            'current_team' => $team->slug,
            'quote' => $quote->id,
        ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $quote->refresh();
        $booking = $quote->convertedInquiry;

        $this->assertNotNull($booking);
        $this->assertSame($customer->id, $booking->customer_id);
        $this->assertDatabaseHas('listing_unavailability', [
            'listing_id' => $listing->id,
            'date' => $quote->start_date->toDateString(),
            'status' => 'booked',
        ]);

        // Contract creation, publication and portal acceptance.
        $this->post(route('bookings.contracts.store', [
            'current_team' => $team->slug,
            'inquiry' => $booking->id,
        ]), [
            'title' => 'Contrato de renta',
            'body' => 'Términos del smoke test. Anticipo no reembolsable.',
        ])->assertSessionHasNoErrors();

        $contract = BookingContract::query()->firstOrFail();

        $this->post(route('bookings.contracts.publish', [
            'current_team' => $team->slug,
            'contract' => $contract->id,
        ]))->assertSessionHasNoErrors();

        $contract->refresh();
        $this->assertSame(ContractStatus::Sent, $contract->status);

        $this->get(route('public.bookings.portal', ['token' => $booking->portal_token]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('booking.id', $booking->id)
                ->where('booking.contract.id', $contract->id)
                ->where('booking.contract.can_accept', true));

        $this->post(route('public.bookings.portal.confirm', [
            'token' => $booking->portal_token,
        ]), [
            'name' => 'Cliente Smoke Confirmado',
            'phone' => '5559999999',
            'whatsapp_phone' => '5215559999999',
            'guests' => 50,
        ])->assertSessionHasNoErrors();

        $booking->refresh();
        $this->assertNotNull($booking->customer_confirmed_at);
        Notification::assertSentTo($owner, BookingConfirmedByCustomer::class);

        $this->post(route('public.bookings.portal.contract.accept', [
            'token' => $booking->portal_token,
        ]), [
            'name' => 'Cliente Smoke Confirmado',
            'accepted' => true,
        ])->assertSessionHasNoErrors();

        $contract->refresh();
        $this->assertSame(ContractStatus::Accepted, $contract->status);
        $this->assertSame('Cliente Smoke Confirmado', $contract->accepted_by_name);
        Notification::assertSentTo($owner, BookingContractAccepted::class);

        $this->get(route('public.bookings.portal.contract', [
            'token' => $booking->portal_token,
        ]))->assertOk();

        $this->get(route('public.bookings.portal.calendar', [
            'token' => $booking->portal_token,
        ]))->assertOk()->assertHeader('Content-Type', 'text/calendar; charset=UTF-8');

        // Post-event feedback.
        $booking->forceFill([
            'start_date' => now($listing->timezone)->subDays(3)->toDateString(),
            'end_date' => now($listing->timezone)->subDays(2)->toDateString(),
        ])->save();

        $this->get(route('public.bookings.portal', ['token' => $booking->portal_token]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('booking.can_feedback', true));

        $this->post(route('public.bookings.portal.feedback', [
            'token' => $booking->portal_token,
        ]), [
            'rating' => 5,
            'comment' => 'Excelente experiencia en el smoke test.',
        ])->assertSessionHasNoErrors();

        $booking->refresh();
        $this->assertSame(5, $booking->feedback_rating);
        Notification::assertSentTo($owner, BookingFeedbackReceived::class);

        // Reports reflect the completed flow.
        $this->get(route('bookings.reports', ['current_team' => $team->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('overview.inquiries', 2)
                ->where('overview.accepted', 1)
                ->where('overview.quotes', 1)
                ->where('overview.quotes_accepted', 1)
                ->where('overview.feedback_count', 1));
    }

    private function subscribePro(Team $team): void
    {
        config(['billing.plans.pro.stripe_price_id' => 'price_pro_test']);

        Subscription::query()->create([
            'team_id' => $team->id,
            'type' => 'default',
            'stripe_id' => 'sub_'.fake()->unique()->bothify('??####'),
            'stripe_status' => 'active',
            'stripe_price' => 'price_pro_test',
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null,
        ]);
    }
}
