<?php

namespace Modules\Listings\Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Listings\Enums\ListingServicePriceUnit;
use Modules\Listings\Enums\ListingServicePricingMode;
use Modules\Listings\Enums\ListingServiceProvider;
use Modules\Listings\Models\Listing;
use Modules\Listings\Models\ListingService;

/**
 * @extends Factory<ListingService>
 */
class ListingServiceFactory extends Factory
{
    protected $model = ListingService::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'listing_id' => Listing::factory(),
            'service_key' => 'dj',
            'description' => fake()->sentence(),
            'is_included' => false,
            'pricing_mode' => ListingServicePricingMode::Additional,
            'provider' => ListingServiceProvider::Own,
            'price_cents' => 250000,
            'price_unit' => ListingServicePriceUnit::Event,
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
