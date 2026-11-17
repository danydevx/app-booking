<?php

namespace Modules\Listings\Tests\Feature;

use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Listings\Enums\ListingStatus;
use Modules\Listings\Models\Listing;
use Tests\TestCase;

class ListingImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_creates_directory_listings_and_is_idempotent(): void
    {
        $this->artisan('app:directory-team')->assertExitCode(0);

        $file = $this->jsonFile([
            [
                'external_ref' => 'terraza-encinos',
                'name' => 'Jardín Los Encinos',
                'description' => 'Terraza para eventos.',
                'address' => 'Av. Vallarta 123',
                'city' => 'Guadalajara',
                'state' => 'Jalisco',
                'postal_code' => '44100',
                'latitude' => 20.6736,
                'longitude' => -103.4055,
                'capacity' => 150,
                'services' => ['wifi', 'estacionamiento'],
                'contact_email' => 'contacto@encinos.test',
                'price_from_cents' => 1500000,
            ],
        ]);

        $this->artisan('listings:import', ['file' => $file])->assertExitCode(0);

        $directory = Team::query()->where('is_directory', true)->firstOrFail();
        $listing = Listing::query()->where('external_ref', 'terraza-encinos')->firstOrFail();

        $this->assertSame($directory->id, $listing->team_id);
        $this->assertSame('Jardín Los Encinos', $listing->name);
        $this->assertSame('Guadalajara', $listing->city);
        $this->assertSame(150, $listing->capacity);
        $this->assertSame(1500000, $listing->price_from_cents);
        $this->assertSame(['wifi', 'estacionamiento'], $listing->services);
        $this->assertSame(ListingStatus::Draft, $listing->status);
        $this->assertNull($listing->claimed_at);

        file_put_contents($file, json_encode([
            [
                'external_ref' => 'terraza-encinos',
                'name' => 'Jardín Los Encinos Actualizado',
                'city' => 'Guadalajara',
                'state' => 'Jalisco',
            ],
        ], JSON_UNESCAPED_UNICODE));

        $this->artisan('listings:import', ['file' => $file])->assertExitCode(0);

        $this->assertSame(1, Listing::query()->count());
        $this->assertSame('Jardín Los Encinos Actualizado', $listing->fresh()->name);

        @unlink($file);
    }

    public function test_import_publishes_a_listing_with_images_and_coordinates(): void
    {
        Storage::fake('public');

        $this->artisan('app:directory-team')->assertExitCode(0);

        $imagesDirectory = sys_get_temp_dir().'/listing-import-'.uniqid();
        mkdir($imagesDirectory);
        file_put_contents(
            $imagesDirectory.'/foto.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+ip1sAAAAASUVORK5CYII='),
        );

        $file = $this->jsonFile([
            [
                'external_ref' => 'terraza-publica',
                'name' => 'Terraza Publicable',
                'address' => 'Av. Chapultepec 10',
                'city' => 'Guadalajara',
                'state' => 'Jalisco',
                'latitude' => 20.674,
                'longitude' => -103.37,
                'contact_email' => 'contacto@publicable.test',
                'images' => ['foto.png'],
            ],
        ]);

        $this->artisan('listings:import', [
            'file' => $file,
            '--images' => $imagesDirectory,
            '--publish' => true,
        ])->assertExitCode(0);

        $listing = Listing::query()->where('external_ref', 'terraza-publica')->firstOrFail();

        $this->assertSame(ListingStatus::Published, $listing->status);
        $this->assertNotNull($listing->published_at);
        $this->assertSame(1, $listing->images()->count());

        @unlink($file);
        @unlink($imagesDirectory.'/foto.png');
        @rmdir($imagesDirectory);
    }

    public function test_import_does_not_overwrite_a_listing_after_it_was_claimed(): void
    {
        $this->artisan('app:directory-team')->assertExitCode(0);

        $file = $this->jsonFile([
            [
                'external_ref' => 'terraza-reclamada',
                'name' => 'Terraza Original',
                'city' => 'Guadalajara',
                'state' => 'Jalisco',
            ],
        ]);

        $this->artisan('listings:import', ['file' => $file])->assertExitCode(0);

        $listing = Listing::query()->where('external_ref', 'terraza-reclamada')->firstOrFail();
        $listing->forceFill(['claimed_at' => now()])->save();

        file_put_contents($file, json_encode([
            [
                'external_ref' => 'terraza-reclamada',
                'name' => 'Terraza Modificada',
            ],
        ], JSON_UNESCAPED_UNICODE));

        $this->artisan('listings:import', ['file' => $file])->assertExitCode(1);

        $this->assertSame('Terraza Original', $listing->fresh()->name);

        @unlink($file);
    }

    /**
     * @param  array<int, array<string, mixed>>  $payload
     */
    private function jsonFile(array $payload): string
    {
        $file = sys_get_temp_dir().'/listings-import-'.uniqid().'.json';
        file_put_contents($file, json_encode($payload, JSON_UNESCAPED_UNICODE));

        return $file;
    }
}
