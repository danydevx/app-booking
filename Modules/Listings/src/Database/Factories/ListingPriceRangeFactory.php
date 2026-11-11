<?php

namespace Modules\Listings\Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Listings\Models\Listing;
use Modules\Listings\Models\ListingPriceRange;

/**
 * @extends Factory<ListingPriceRange>
 */
class ListingPriceRangeFactory extends Factory
{
    protected $model = ListingPriceRange::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'listing_id' => Listing::factory(),
            'name' => 'Temporada '.fake()->word(),
            'starts_at' => now()->subDay()->toDateString(),
            'ends_at' => now()->addDays(30)->toDateString(),
            'price_cents' => 250000,
            'daily_pricing' => [
                'monday' => ['hours' => 8, 'entry_time' => '12:00', 'exit_time' => '20:00', 'price_cents' => 250000],
                'tuesday' => ['hours' => 8, 'entry_time' => '12:00', 'exit_time' => '20:00', 'price_cents' => 250000],
                'wednesday' => ['hours' => 8, 'entry_time' => '12:00', 'exit_time' => '20:00', 'price_cents' => 250000],
                'thursday' => ['hours' => 8, 'entry_time' => '12:00', 'exit_time' => '20:00', 'price_cents' => 250000],
                'friday' => ['hours' => 8, 'entry_time' => '12:00', 'exit_time' => '20:00', 'price_cents' => 250000],
                'saturday' => ['hours' => 8, 'entry_time' => '12:00', 'exit_time' => '20:00', 'price_cents' => 250000],
                'sunday' => ['hours' => 8, 'entry_time' => '12:00', 'exit_time' => '20:00', 'price_cents' => 250000],
            ],
            'included_hours' => 8,
            'included_people' => 50,
            'extra_hour_cents' => 40000,
            'extra_person_cents' => 5000,
            'deposit_required_cents' => 100000,
            'conditions' => 'Precio por evento.',
            'active' => true,
        ];
    }

    public function forListing(Listing $listing): static
    {
        return $this->state(fn (): array => [
            'team_id' => $listing->team_id,
            'listing_id' => $listing->id,
        ]);
    }
}
