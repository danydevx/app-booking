<?php

namespace Tests\Feature;

use Database\Seeders\JaliscoTerracesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Listings\Models\Listing;
use Modules\Platform\Models\GeoMunicipality;
use Modules\Platform\Models\GeoState;
use Tests\TestCase;

class JaliscoTerracesSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_ten_complete_jalisco_listings_idempotently(): void
    {
        Storage::fake('public');
        config(['listings.media.disk' => 'public']);

        $this->seed(JaliscoTerracesSeeder::class);

        $listings = Listing::query()
            ->where('state', 'Jalisco')
            ->withCount(['media', 'eventTypes', 'packages', 'commercialServices', 'promotions', 'priceRanges'])
            ->get();

        $this->assertCount(10, $listings);
        $this->assertCount(10, $listings->pluck('slug')->unique());
        $this->assertCount(10, $listings->pluck('city')->unique());

        $jalisco = GeoState::query()->where('code', '14')->firstOrFail();
        $municipalities = GeoMunicipality::query()
            ->where('geo_state_id', $jalisco->id)
            ->pluck('name')
            ->all();

        foreach ($listings as $listing) {
            $this->assertContains($listing->city, $municipalities);
            $this->assertSame('published', $listing->status->value);
            $this->assertSame(4, $listing->media_count);
            $this->assertGreaterThanOrEqual(5, $listing->event_types_count);
            $this->assertSame(3, $listing->packages_count);
            $this->assertSame(6, $listing->commercial_services_count);
            $this->assertSame(2, $listing->promotions_count);
            $this->assertSame(2, $listing->price_ranges_count);
            $this->assertNotNull($listing->price_from_cents);
            $this->assertNotNull($listing->daily_pricing);
            $this->assertNotEmpty($listing->details);
            $this->assertNotEmpty($listing->services);

            foreach ($listing->media as $media) {
                $this->assertTrue(Storage::disk($media->disk)->exists($media->path));
            }
        }

        $this->assertSame(10, Listing::query()->publiclyVisible()->count());

        $this->seed(JaliscoTerracesSeeder::class);

        $this->assertSame(10, Listing::query()->where('state', 'Jalisco')->count());
        $this->assertSame(40, Listing::query()->where('state', 'Jalisco')->withCount('media')->get()->sum('media_count'));
    }
}
