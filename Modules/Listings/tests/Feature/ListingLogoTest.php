<?php

namespace Modules\Listings\Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Listings\Models\Listing;
use Tests\TestCase;

class ListingLogoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_owner_can_upload_a_listing_logo(): void
    {
        [$owner, $team] = $this->ownerWithTeam();

        $this->actingAs($owner)
            ->post(route('listings.store', ['current_team' => $team->slug]), [
                'name' => 'Terraza con logo',
                'logo' => UploadedFile::fake()->image('logo.png', 800, 400),
            ])
            ->assertSessionHasNoErrors();

        $listing = Listing::query()->firstOrFail();

        $this->assertNotNull($listing->logo_path);
        $this->assertStringStartsWith('listings/'.$listing->id.'/logo-', (string) $listing->logo_path);
        Storage::disk('public')->assertExists((string) $listing->logo_path);
        $this->assertNotNull($listing->logoUrl());
        $this->assertNotSame($listing->logo_path, $listing->coverImage?->path);
    }

    public function test_owner_can_replace_and_delete_the_logo(): void
    {
        [$owner, $team] = $this->ownerWithTeam();
        $listing = Listing::factory()->forTeam($team)->create();

        $this->actingAs($owner)
            ->patch(route('listings.update', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]), [
                'name' => $listing->name,
                'logo' => UploadedFile::fake()->image('first.png', 600, 300),
            ])
            ->assertSessionHasNoErrors();

        $listing->refresh();
        $first = (string) $listing->logo_path;

        Storage::disk('public')->assertExists($first);

        $this->actingAs($owner)
            ->patch(route('listings.update', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]), [
                'name' => $listing->name,
                'logo' => UploadedFile::fake()->image('second.jpg', 600, 300),
            ])
            ->assertSessionHasNoErrors();

        $listing->refresh();
        $second = (string) $listing->logo_path;

        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);

        $this->actingAs($owner)
            ->patch(route('listings.update', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]), [
                'name' => $listing->name,
                'remove_logo' => true,
            ])
            ->assertSessionHasNoErrors();

        $listing->refresh();

        $this->assertNull($listing->logo_path);
        $this->assertNull($listing->logoUrl());
        Storage::disk('public')->assertMissing($second);
    }

    public function test_non_image_files_are_rejected_as_logo(): void
    {
        [$owner, $team] = $this->ownerWithTeam();

        $this->actingAs($owner)
            ->post(route('listings.store', ['current_team' => $team->slug]), [
                'name' => 'Terraza sin logo válido',
                'logo' => UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('logo');

        $this->assertDatabaseCount('listings', 0);
    }

    /**
     * @return array{0: User, 1: Team}
     */
    private function ownerWithTeam(): array
    {
        $owner = User::factory()->create();

        return [$owner, $owner->currentTeam];
    }
}
