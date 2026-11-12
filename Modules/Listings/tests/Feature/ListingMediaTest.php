<?php

namespace Modules\Listings\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Listings\Actions\GenerateListingMediaThumbnail;
use Modules\Listings\Enums\ListingMediaType;
use Modules\Listings\Models\Listing;
use Modules\Listings\Models\ListingMedia;
use Tests\TestCase;

class ListingMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_upload_an_image(): void
    {
        Storage::fake('public');

        [$owner, $team, $listing] = $this->ownedListing();

        $response = $this->actingAs($owner)->post(
            route('listings.media.store', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]),
            [
                'alt_text' => 'Terraza con vista',
                'file' => UploadedFile::fake()->image('terraza.jpg', 1200, 800),
            ],
        );

        $response->assertRedirect();

        $media = ListingMedia::query()->firstOrFail();

        $this->assertSame($listing->id, $media->listing_id);
        $this->assertSame(ListingMediaType::Image, $media->type);
        $this->assertSame('image/jpeg', $media->mime_type);
        $this->assertSame(1200, $media->width);
        $this->assertSame(800, $media->height);
        $this->assertSame('Terraza con vista', $media->alt_text);
        Storage::disk('public')->assertExists($media->path);
        $this->assertSquareThumbnail($media);
    }

    public function test_upload_generates_square_thumbnails_for_any_image_orientation(): void
    {
        Storage::fake('public');

        [$owner, $team, $listing] = $this->ownedListing();
        $route = route('listings.media.store', [
            'current_team' => $team->slug,
            'listing' => $listing->slug,
        ]);

        foreach ([[1200, 800], [800, 1200], [1000, 1000]] as [$width, $height]) {
            $this->actingAs($owner)->post($route, [
                'file' => UploadedFile::fake()->image(
                    "foto-{$width}x{$height}.jpg",
                    $width,
                    $height,
                ),
            ])->assertRedirect();
        }

        $media = ListingMedia::query()->orderBy('id')->get();

        $this->assertCount(3, $media);

        foreach ($media as $item) {
            $this->assertSquareThumbnail($item);

            $original = getimagesize(Storage::disk('public')->path($item->path));

            $this->assertNotFalse($original);
            $this->assertSame($item->width, $original[0]);
            $this->assertSame($item->height, $original[1]);
        }

        $this->assertCount(
            3,
            Storage::disk('public')->files('listings/'.$listing->id.'/thumbs'),
        );
    }

    public function test_thumbnail_generation_keeps_the_original_and_is_idempotent(): void
    {
        Storage::fake('public');

        [, , $listing] = $this->ownedListing();
        $path = 'listings/'.$listing->id.'/original.jpg';
        $media = ListingMedia::factory()->for($listing)->create([
            'disk' => 'public',
            'path' => $path,
            'width' => 900,
            'height' => 600,
            'sort_order' => 0,
        ]);

        $file = UploadedFile::fake()->image('original.jpg', 900, 600);
        Storage::disk('public')->put($path, $file->getContent());

        $originalHash = hash_file('sha256', Storage::disk('public')->path($path));

        $action = app(GenerateListingMediaThumbnail::class);
        $thumbnailPath = $action->handle($media);

        $this->assertSame($media->thumbnailPath(), $thumbnailPath);
        $this->assertSquareThumbnail($media);
        $this->assertSame(
            $originalHash,
            hash_file('sha256', Storage::disk('public')->path($path)),
        );

        $thumbHash = hash_file(
            'sha256',
            Storage::disk('public')->path((string) $thumbnailPath),
        );

        $action->handle($media);

        $this->assertSame(
            $thumbHash,
            hash_file('sha256', Storage::disk('public')->path((string) $thumbnailPath)),
        );
        $this->assertSame(
            $originalHash,
            hash_file('sha256', Storage::disk('public')->path($path)),
        );
        $this->assertCount(
            1,
            Storage::disk('public')->files('listings/'.$listing->id.'/thumbs'),
        );
    }

    public function test_thumbnail_command_generates_missing_files_and_is_idempotent(): void
    {
        Storage::fake('public');

        [, , $listing] = $this->ownedListing();
        $path = 'listings/'.$listing->id.'/command.jpg';
        $media = ListingMedia::factory()->for($listing)->create([
            'disk' => 'public',
            'path' => $path,
            'width' => 800,
            'height' => 1200,
            'sort_order' => 0,
        ]);

        $file = UploadedFile::fake()->image('command.jpg', 800, 1200);
        Storage::disk('public')->put($path, $file->getContent());

        $originalHash = hash_file('sha256', Storage::disk('public')->path($path));

        $this->artisan('listings:media:thumbnails', ['--dry-run' => true])
            ->assertSuccessful();

        $this->assertFalse(
            Storage::disk('public')->exists((string) $media->thumbnailPath()),
        );

        $this->artisan('listings:media:thumbnails')->assertSuccessful();

        $this->assertSquareThumbnail($media);
        $this->assertSame(
            $originalHash,
            hash_file('sha256', Storage::disk('public')->path($path)),
        );

        $thumbHash = hash_file(
            'sha256',
            Storage::disk('public')->path((string) $media->thumbnailPath()),
        );

        $this->artisan('listings:media:thumbnails')->assertSuccessful();

        $this->assertSame(
            $thumbHash,
            hash_file(
                'sha256',
                Storage::disk('public')->path((string) $media->thumbnailPath()),
            ),
        );
        $this->assertCount(
            1,
            Storage::disk('public')->files('listings/'.$listing->id.'/thumbs'),
        );
    }

    public function test_thumbnail_command_skips_missing_originals(): void
    {
        Storage::fake('public');

        [, , $listing] = $this->ownedListing();
        $media = ListingMedia::factory()->for($listing)->create([
            'disk' => 'public',
            'path' => 'listings/'.$listing->id.'/missing.jpg',
            'sort_order' => 0,
        ]);

        $this->artisan('listings:media:thumbnails')->assertSuccessful();

        $this->assertFalse(
            Storage::disk('public')->exists((string) $media->thumbnailPath()),
        );
    }

    public function test_thumbnail_generation_respects_exif_orientation(): void
    {
        Storage::fake('public');

        [$owner, $team, $listing] = $this->ownedListing();

        $this->actingAs($owner)->post(
            route('listings.media.store', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]),
            [
                'file' => UploadedFile::fake()->createWithContent(
                    'exif-orientation.jpg',
                    $this->redBlueJpegWithOrientation(6),
                ),
            ],
        )->assertRedirect();

        $media = ListingMedia::query()->firstOrFail();

        $this->assertSquareThumbnail($media);

        $thumbnail = imagecreatefromwebp(
            Storage::disk('public')->path((string) $media->thumbnailPath()),
        );

        $this->assertNotFalse($thumbnail);

        $top = imagecolorsforindex($thumbnail, imagecolorat($thumbnail, 200, 100));
        $bottom = imagecolorsforindex($thumbnail, imagecolorat($thumbnail, 200, 300));

        $this->assertGreaterThan(180, $top['red']);
        $this->assertLessThan(80, $top['blue']);
        $this->assertGreaterThan(180, $bottom['blue']);
        $this->assertLessThan(80, $bottom['red']);
    }

    public function test_executable_files_are_rejected(): void
    {
        Storage::fake('public');

        [$owner, $team, $listing] = $this->ownedListing();

        $this->actingAs($owner)->post(
            route('listings.media.store', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]),
            [
                'type' => 'image',
                'file' => UploadedFile::fake()->create('malware.exe', 10, 'application/x-msdownload'),
            ],
        )->assertSessionHasErrors('file');

        $this->assertDatabaseCount('listing_media', 0);
    }

    public function test_mime_mismatch_and_oversized_images_are_rejected(): void
    {
        Storage::fake('public');

        [$owner, $team, $listing] = $this->ownedListing();
        $route = route('listings.media.store', [
            'current_team' => $team->slug,
            'listing' => $listing->slug,
        ]);

        $this->actingAs($owner)->post($route, [
            'type' => 'image',
            'file' => UploadedFile::fake()->create('not-an-image.jpg', 10, 'application/pdf'),
        ])->assertSessionHasErrors('file');

        $this->actingAs($owner)->post($route, [
            'type' => 'image',
            'file' => UploadedFile::fake()->image('huge.jpg')->size(6000),
        ])->assertSessionHasErrors('file');

        $this->assertDatabaseCount('listing_media', 0);
    }

    public function test_media_can_be_reordered_and_deleted(): void
    {
        Storage::fake('public');

        [$owner, $team, $listing] = $this->ownedListing();
        $first = ListingMedia::factory()->for($listing)->create(['sort_order' => 0]);
        $second = ListingMedia::factory()->for($listing)->create(['sort_order' => 1]);
        Storage::disk('public')->put($second->path, 'content');
        Storage::disk('public')->put((string) $second->thumbnailPath(), 'thumb');

        $this->actingAs($owner)->patch(
            route('listings.media.reorder', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]),
            ['ids' => [$second->id, $first->id]],
        )->assertRedirect();

        $this->assertSame(0, $second->fresh()->sort_order);
        $this->assertSame(1, $first->fresh()->sort_order);

        $this->actingAs($owner)->delete(
            route('listings.media.destroy', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
                'media' => $second->id,
            ]),
        )->assertRedirect();

        $this->assertDatabaseMissing('listing_media', ['id' => $second->id]);
        Storage::disk('public')->assertMissing($second->path);
        Storage::disk('public')->assertMissing((string) $second->thumbnailPath());
    }

    public function test_owner_can_update_and_clear_the_alt_text(): void
    {
        [$owner, $team, $listing] = $this->ownedListing();
        $media = ListingMedia::factory()->for($listing)->create([
            'alt_text' => 'Texto anterior',
            'sort_order' => 0,
        ]);

        $route = route('listings.media.update', [
            'current_team' => $team->slug,
            'listing' => $listing->slug,
            'media' => $media->id,
        ]);

        $this->actingAs($owner)
            ->patch($route, ['alt_text' => '  Jardín con luces  '])
            ->assertRedirect();

        $media->refresh();

        $this->assertSame('Jardín con luces', $media->alt_text);
        $this->assertSame(0, $media->sort_order);
        $this->assertSame($listing->id, $media->listing_id);

        $this->actingAs($owner)
            ->patch($route, ['alt_text' => ''])
            ->assertRedirect();

        $this->assertNull($media->fresh()->alt_text);
    }

    public function test_alt_text_update_validates_the_max_length(): void
    {
        [$owner, $team, $listing] = $this->ownedListing();
        $media = ListingMedia::factory()->for($listing)->create([
            'alt_text' => 'Original',
        ]);

        $this->actingAs($owner)
            ->patch(
                route('listings.media.update', [
                    'current_team' => $team->slug,
                    'listing' => $listing->slug,
                    'media' => $media->id,
                ]),
                ['alt_text' => str_repeat('a', 256)],
            )
            ->assertSessionHasErrors('alt_text');

        $this->assertSame('Original', $media->fresh()->alt_text);
    }

    public function test_alt_text_update_is_scoped_to_the_listing_and_team(): void
    {
        [$owner, $team, $listing] = $this->ownedListing();
        [, $otherTeam, $otherListing] = $this->ownedListing();
        $foreignMedia = ListingMedia::factory()->for($otherListing)->create([
            'alt_text' => 'Original',
        ]);

        $this->actingAs($owner)
            ->patch(
                route('listings.media.update', [
                    'current_team' => $team->slug,
                    'listing' => $listing->slug,
                    'media' => $foreignMedia->id,
                ]),
                ['alt_text' => 'Intento'],
            )
            ->assertNotFound();

        $this->actingAs($owner)
            ->patch(
                route('listings.media.update', [
                    'current_team' => $team->slug,
                    'listing' => $otherListing->slug,
                    'media' => $foreignMedia->id,
                ]),
                ['alt_text' => 'Intento'],
            )
            ->assertNotFound();

        $this->actingAs($owner)
            ->patch(
                route('listings.media.update', [
                    'current_team' => $otherTeam->slug,
                    'listing' => $otherListing->slug,
                    'media' => $foreignMedia->id,
                ]),
                ['alt_text' => 'Intento'],
            )
            ->assertForbidden();

        $this->assertSame('Original', $foreignMedia->fresh()->alt_text);
    }

    public function test_video_uploads_are_not_allowed(): void
    {
        Storage::fake('public');

        [$owner, $team, $listing] = $this->ownedListing();

        $this->actingAs($owner)->post(
            route('listings.media.store', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]),
            [
                'type' => 'video',
                'file' => UploadedFile::fake()->create('tour.mp4', 100, 'video/mp4'),
            ],
        )->assertSessionHasErrors('file');

        $this->assertDatabaseCount('listing_media', 0);
    }

    private function assertSquareThumbnail(ListingMedia $media): void
    {
        $thumbnailPath = $media->thumbnailPath();

        $this->assertNotNull($thumbnailPath);
        Storage::disk($media->disk)->assertExists($thumbnailPath);

        $size = getimagesize(Storage::disk($media->disk)->path($thumbnailPath));

        $this->assertNotFalse($size);
        $this->assertSame(400, $size[0]);
        $this->assertSame(400, $size[1]);
        $this->assertSame('image/webp', $size['mime']);
        $this->assertNotNull($media->thumbnail_url);
    }

    private function redBlueJpegWithOrientation(int $orientation): string
    {
        $image = imagecreatetruecolor(200, 100);
        $red = imagecolorallocate($image, 255, 0, 0);
        $blue = imagecolorallocate($image, 0, 0, 255);

        imagefilledrectangle($image, 0, 0, 99, 99, $red);
        imagefilledrectangle($image, 100, 0, 199, 99, $blue);

        ob_start();
        imagejpeg($image);
        $jpeg = (string) ob_get_clean();
        imagedestroy($image);

        $tiff = "II*\0".pack('V', 8).pack('v', 1)
            .pack('v', 0x0112).pack('v', 3).pack('V', 1)
            .pack('v', $orientation).pack('v', 0).pack('V', 0);
        $app1 = "Exif\0\0".$tiff;
        $segment = "\xFF\xE1".pack('n', strlen($app1) + 2).$app1;

        return substr($jpeg, 0, 2).$segment.substr($jpeg, 2);
    }

    /**
     * @return array{0: User, 1: Team, 2: Listing}
     */
    private function ownedListing(): array
    {
        $owner = User::factory()->create();
        $team = $owner->currentTeam;
        $listing = Listing::factory()->forTeam($team)->create();

        return [$owner, $team, $listing];
    }
}
