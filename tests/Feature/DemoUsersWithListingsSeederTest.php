<?php

namespace Tests\Feature;

use Database\Seeders\DemoUsersWithListingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Modules\Listings\Models\Listing;
use Modules\Listings\Models\ListingMedia;
use Tests\TestCase;

class DemoUsersWithListingsSeederTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl2QmcAAAAASUVORK5CYII=';

    public function test_it_creates_five_owners_with_listings_and_images_idempotently(): void
    {
        Storage::fake('public');
        Http::preventStrayRequests();
        Http::fake([
            'placehold.co/*' => Http::response(
                base64_decode(self::PNG),
                200,
                ['Content-Type' => 'image/png'],
            ),
        ]);

        $this->seed(DemoUsersWithListingsSeeder::class);
        $this->seed(DemoUsersWithListingsSeeder::class);

        $emails = [
            'ana.torres@terrazas.test',
            'bruno.salinas@terrazas.test',
            'carla.mendez@terrazas.test',
            'diego.ramirez@terrazas.test',
            'elena.vargas@terrazas.test',
        ];

        $this->assertDatabaseCount('users', 5);
        $this->assertDatabaseCount('listings', 5);
        $this->assertDatabaseCount('listing_media', 15);
        $this->assertDatabaseHas('users', ['email' => $emails[0]]);
        $this->assertDatabaseHas('users', ['email' => $emails[4]]);
        $this->assertSame(5, Listing::query()->whereHas('media')->count());
        $this->assertSame(15, ListingMedia::query()->count());

        $listing = Listing::query()->firstOrFail();
        $this->assertCount(3, Storage::disk('public')->files("listings/{$listing->id}"));

        Http::assertSentCount(15);
    }
}
