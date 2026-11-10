<?php

namespace Modules\Bookings\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Cashier\Subscription;
use Modules\Bookings\Enums\QuoteStatus;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Models\CustomerQuote;
use Modules\Bookings\Notifications\CustomerQuoteAccepted;
use Modules\Bookings\Notifications\CustomerQuoteSent;
use Modules\Listings\Models\Listing;
use Tests\TestCase;

class CustomerQuoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_pro_owner_can_create_a_quote_from_a_customer(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);
        $start = now($listing->timezone)->addDays(10);

        $this->actingAs($owner)
            ->post(route('bookings.customers.quotes.store', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
            ]), [
                'listing_id' => $listing->id,
                'start_date' => $start->toDateString(),
                'end_date' => $start->copy()->addDay()->toDateString(),
                'event_type' => 'Cumpleaños',
                'guests' => 50,
                'subtotal' => '8500.50',
                'deposit' => '2000.00',
                'notes' => 'Incluye mesas y sillas.',
                'terms' => 'Anticipo no reembolsable.',
                'expires_at' => now()->addDays(5)->toDateTimeString(),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $quote = CustomerQuote::query()->firstOrFail();

        $this->assertSame($team->id, $quote->team_id);
        $this->assertSame($customer->id, $quote->customer_id);
        $this->assertSame($listing->id, $quote->listing_id);
        $this->assertSame(850050, $quote->subtotal_cents);
        $this->assertSame(200000, $quote->deposit_cents);
        $this->assertSame(QuoteStatus::Draft, $quote->status);
        $this->assertNotEmpty($quote->public_token);

        $this->assertDatabaseHas('customer_activities', [
            'customer_id' => $customer->id,
            'body' => "Cotización creada para {$listing->name}.",
        ]);
    }

    public function test_free_plan_cannot_create_quotes(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $customer = Customer::factory()->create(['team_id' => $team->id]);
        $start = now($listing->timezone)->addDays(10);

        $this->actingAs($owner)
            ->post(route('bookings.customers.quotes.store', [
                'current_team' => $team->slug,
                'customer' => $customer->id,
            ]), [
                'listing_id' => $listing->id,
                'start_date' => $start->toDateString(),
                'end_date' => $start->copy()->addDay()->toDateString(),
                'subtotal' => '1000',
            ])
            ->assertRedirect(route('billing.index', ['current_team' => $team->slug]));

        $this->assertDatabaseCount('customer_quotes', 0);
    }

    public function test_owner_can_send_a_quote_by_email(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);
        $quote = $this->quoteFor($customer, $listing);

        Notification::fake();

        $this->actingAs($owner)
            ->post(route('bookings.quotes.send', [
                'current_team' => $team->slug,
                'quote' => $quote->id,
            ]))
            ->assertSessionHasNoErrors();

        Notification::assertSentOnDemand(CustomerQuoteSent::class);

        $quote->refresh();

        $this->assertSame(QuoteStatus::Sent, $quote->status);
        $this->assertNotNull($quote->sent_at);
        $this->assertDatabaseHas('customer_activities', [
            'customer_id' => $customer->id,
            'body' => "Cotización enviada para {$listing->name}.",
        ]);
    }

    public function test_public_quote_can_be_accepted_and_notifies_the_owner(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);
        $quote = $this->quoteFor($customer, $listing, [
            'status' => QuoteStatus::Sent,
            'sent_at' => now(),
        ]);

        Notification::fake();

        $this->get(route('public.quotes.show', ['token' => $quote->public_token]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Bookings/Public/Quote')
                ->where('quote.id', $quote->id)
                ->where('quote.can_respond', true));

        $this->post(route('public.quotes.accept', ['token' => $quote->public_token]))
            ->assertSessionHasNoErrors();

        $quote->refresh();

        $this->assertSame(QuoteStatus::Accepted, $quote->status);
        $this->assertNotNull($quote->accepted_at);
        Notification::assertSentTo($owner, CustomerQuoteAccepted::class);
    }

    public function test_expired_public_quote_cannot_be_accepted(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);
        $quote = $this->quoteFor($customer, $listing, [
            'status' => QuoteStatus::Sent,
            'sent_at' => now()->subDay(),
            'expires_at' => now()->subHour(),
        ]);

        $this->post(route('public.quotes.accept', ['token' => $quote->public_token]));

        $this->assertSame(QuoteStatus::Sent, $quote->fresh()->status);
    }

    public function test_public_quote_with_invalid_token_is_not_found(): void
    {
        $this->get(route('public.quotes.show', ['token' => 'token-invalido']))
            ->assertNotFound();
    }

    public function test_owner_can_convert_an_accepted_quote_into_a_booking(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $customer = Customer::factory()->create([
            'team_id' => $team->id,
            'phone' => '5551234567',
        ]);
        $start = now($listing->timezone)->addDays(12);
        $quote = $this->quoteFor($customer, $listing, [
            'status' => QuoteStatus::Accepted,
            'accepted_at' => now(),
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addDay()->toDateString(),
        ]);

        $this->actingAs($owner)
            ->post(route('bookings.quotes.convert', [
                'current_team' => $team->slug,
                'quote' => $quote->id,
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $quote->refresh();

        $this->assertNotNull($quote->converted_inquiry_id);
        $inquiry = BookingInquiry::query()->findOrFail($quote->converted_inquiry_id);
        $this->assertSame($quote->start_date->toDateString(), $inquiry->start_date->toDateString());
        $this->assertSame($customer->id, $inquiry->customer_id);

        $this->assertDatabaseHas('listing_unavailability', [
            'listing_id' => $listing->id,
            'date' => $quote->start_date->toDateString(),
            'status' => 'booked',
        ]);
    }

    public function test_quote_routes_are_isolated_by_team(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $foreignTeam = Team::factory()->create();
        $foreignCustomer = Customer::factory()->create(['team_id' => $foreignTeam->id]);
        $foreignListing = Listing::factory()->forTeam($foreignTeam)->create();
        $foreignQuote = $this->quoteFor($foreignCustomer, $foreignListing);

        $this->actingAs($owner)
            ->get(route('bookings.quotes.show', [
                'current_team' => $team->slug,
                'quote' => $foreignQuote->id,
            ]))
            ->assertNotFound();
    }

    public function test_quote_index_filters_by_status_and_listing(): void
    {
        [$owner, $team, $listing] = $this->ownerListing();
        $this->subscribePro($team);
        $customer = Customer::factory()->create(['team_id' => $team->id]);
        $sent = $this->quoteFor($customer, $listing, ['status' => QuoteStatus::Sent]);
        $this->quoteFor($customer, $listing, ['status' => QuoteStatus::Draft]);

        $this->actingAs($owner)
            ->get(route('bookings.quotes.index', [
                'current_team' => $team->slug,
                'status' => QuoteStatus::Sent->value,
                'listing' => $listing->slug,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Bookings/Quotes/Index')
                ->has('quotes.data', 1)
                ->where('quotes.data.0.id', $sent->id));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function quoteFor(
        Customer $customer,
        Listing $listing,
        array $attributes = [],
    ): CustomerQuote {
        $start = now($listing->timezone)->addDays(10);

        return CustomerQuote::factory()
            ->forCustomer($customer)
            ->create([
                'listing_id' => $listing->id,
                'start_date' => $start->toDateString(),
                'end_date' => $start->copy()->addDay()->toDateString(),
                ...$attributes,
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
