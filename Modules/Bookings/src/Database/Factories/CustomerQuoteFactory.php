<?php

namespace Modules\Bookings\Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Bookings\Enums\QuoteStatus;
use Modules\Bookings\Models\Customer;
use Modules\Bookings\Models\CustomerQuote;
use Modules\Listings\Models\Listing;

/**
 * @extends Factory<CustomerQuote>
 */
class CustomerQuoteFactory extends Factory
{
    protected $model = CustomerQuote::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->addDays(10)->startOfDay();

        return [
            'team_id' => Team::factory(),
            'customer_id' => Customer::factory(),
            'listing_id' => Listing::factory(),
            'created_by' => null,
            'converted_inquiry_id' => null,
            'status' => QuoteStatus::Draft,
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addDay()->toDateString(),
            'event_type' => 'Cumpleaños',
            'guests' => 40,
            'subtotal_cents' => 500000,
            'deposit_cents' => 100000,
            'currency' => 'MXN',
            'notes' => null,
            'terms' => null,
            'expires_at' => now()->addDays(7),
        ];
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn (): array => [
            'team_id' => $customer->team_id,
            'customer_id' => $customer->id,
        ]);
    }
}
