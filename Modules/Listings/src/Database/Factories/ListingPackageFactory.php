<?php

namespace Modules\Listings\Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Listings\Models\Listing;
use Modules\Listings\Models\ListingPackage;

/**
 * @extends Factory<ListingPackage>
 */
class ListingPackageFactory extends Factory
{
    protected $model = ListingPackage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'listing_id' => Listing::factory(),
            'name' => 'Paquete '.fake()->word(),
            'description' => fake()->sentence(),
            'price_cents' => 500000,
            'duration_hours' => 8,
            'included_people' => 50,
            'included_services' => ['dj'],
            'conditions' => 'Anticipo no reembolsable.',
            'active' => true,
            'sort_order' => 0,
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
