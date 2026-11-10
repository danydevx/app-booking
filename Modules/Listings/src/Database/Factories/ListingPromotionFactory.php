<?php

namespace Modules\Listings\Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Listings\Models\Listing;
use Modules\Listings\Models\ListingPromotion;

/**
 * @extends Factory<ListingPromotion>
 */
class ListingPromotionFactory extends Factory
{
    protected $model = ListingPromotion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'listing_id' => Listing::factory(),
            'title' => 'Promoción '.fake()->word(),
            'description' => fake()->sentence(),
            'promotional_price_cents' => 160000,
            'previous_price_cents' => 190000,
            'starts_at' => now()->subDay()->toDateString(),
            'ends_at' => now()->addDays(30)->toDateString(),
            'applies_on' => ['mon', 'tue', 'wed', 'thu'],
            'conditions' => 'No válida en días festivos.',
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
