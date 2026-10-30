<?php

namespace Modules\Bookings\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Bookings\Enums\AvailabilityStatus;
use Modules\Bookings\Models\ListingUnavailability;
use Modules\Listings\Models\Listing;

/**
 * @extends Factory<ListingUnavailability>
 */
class ListingUnavailabilityFactory extends Factory
{
    protected $model = ListingUnavailability::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'listing_id' => Listing::factory(),
            'date' => now()->addDays(10)->toDateString(),
            'status' => AvailabilityStatus::Blocked,
            'reason' => fake()->sentence(),
            'booking_inquiry_id' => null,
            'created_by' => null,
        ];
    }

    public function forListing(Listing $listing): static
    {
        return $this->state(fn (): array => ['listing_id' => $listing->id]);
    }

    public function booked(): static
    {
        return $this->state(fn (): array => [
            'status' => AvailabilityStatus::Booked,
            'reason' => 'Reserva aceptada',
        ]);
    }
}
