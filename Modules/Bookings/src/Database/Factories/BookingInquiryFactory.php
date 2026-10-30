<?php

namespace Modules\Bookings\Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Bookings\Enums\InquiryStatus;
use Modules\Bookings\Models\BookingInquiry;
use Modules\Bookings\Models\Customer;
use Modules\Listings\Models\Listing;

/**
 * @extends Factory<BookingInquiry>
 */
class BookingInquiryFactory extends Factory
{
    protected $model = BookingInquiry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->addDays(10)->startOfDay();

        return [
            'team_id' => Team::factory(),
            'listing_id' => Listing::factory(),
            'customer_id' => null,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'whatsapp_phone' => null,
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addDay()->toDateString(),
            'event_type' => 'Cumpleaños',
            'guests' => fake()->numberBetween(10, 80),
            'message' => fake()->sentence(),
            'status' => InquiryStatus::New,
            'source' => 'public_web',
            'consent_at' => now(),
            'ip_hash' => null,
        ];
    }

    public function forListing(Listing $listing): static
    {
        return $this->state(fn (): array => [
            'team_id' => $listing->team_id,
            'listing_id' => $listing->id,
        ]);
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn (): array => [
            'team_id' => $customer->team_id,
            'customer_id' => $customer->id,
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (): array => [
            'status' => InquiryStatus::Accepted,
            'accepted_at' => now(),
        ]);
    }

    public function declined(): static
    {
        return $this->state(fn (): array => [
            'status' => InquiryStatus::Declined,
            'declined_at' => now(),
        ]);
    }
}
