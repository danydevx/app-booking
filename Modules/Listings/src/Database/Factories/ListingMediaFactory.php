<?php

namespace Modules\Listings\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Listings\Enums\ListingMediaType;
use Modules\Listings\Models\Listing;
use Modules\Listings\Models\ListingMedia;

/**
 * @extends Factory<ListingMedia>
 */
class ListingMediaFactory extends Factory
{
    protected $model = ListingMedia::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'listing_id' => Listing::factory(),
            'type' => ListingMediaType::Image,
            'disk' => config('listings.media.disk', 'public'),
            'path' => 'listings/'.fake()->uuid().'.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 1024,
            'width' => 1200,
            'height' => 800,
            'duration_seconds' => null,
            'alt_text' => fake()->sentence(4),
            'sort_order' => 0,
        ];
    }

    public function image(): static
    {
        return $this->state(fn (): array => [
            'type' => ListingMediaType::Image,
            'mime_type' => 'image/jpeg',
            'width' => 1200,
            'height' => 800,
            'duration_seconds' => null,
        ]);
    }

    public function video(): static
    {
        return $this->state(fn (): array => [
            'type' => ListingMediaType::Video,
            'mime_type' => 'video/mp4',
            'width' => null,
            'height' => null,
            'duration_seconds' => null,
        ]);
    }
}
