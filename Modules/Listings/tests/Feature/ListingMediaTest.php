<?php

namespace Modules\Listings\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
