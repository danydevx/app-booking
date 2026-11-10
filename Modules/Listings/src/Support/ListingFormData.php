<?php

namespace Modules\Listings\Support;

use App\Models\Team;
use Modules\Billing\Support\Entitlements;
use Modules\Listings\Models\Listing;

final class ListingFormData
{
    public function __construct(
        private readonly Entitlements $entitlements,
    ) {}

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function services(): array
    {
        return TerraceServiceCatalog::options();
    }

    /**
     * @return array<string, array{label: string, type: string, options?: array<string, string>, placeholder?: string}>
     */
    public function detailFields(): array
    {
        return TerraceDetailCatalog::all();
    }

    /**
     * @return array<string, array{label: string, municipalities: array<int, string>}>
     */
    public function locations(): array
    {
        return MexicoLocationCatalog::all();
    }

    /**
     * @return array<string, int>
     */
    public function mediaLimits(Team $team): array
    {
        return [
            'max_images' => min(
                (int) config('listings.media.max_images', 20),
                $this->entitlements->limit($team, 'photos_per_listing'),
            ),
            'max_videos' => min(
                (int) config('listings.media.max_videos', 3),
                $this->entitlements->limit($team, 'videos_per_listing'),
            ),
            'max_image_kb' => (int) config('listings.media.max_image_kb', 5120),
            'max_video_kb' => (int) config('listings.media.max_video_kb', 51200),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function ownerUrls(Team $team, Listing $listing): array
    {
        return [
            'index' => route('listings.index', ['current_team' => $team->slug]),
            'update' => route('listings.update', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]),
            'publish' => route('listings.publish', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]),
            'unpublish' => route('listings.unpublish', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]),
            'destroy' => route('listings.destroy', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]),
            'media_store' => route('listings.media.store', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]),
            'media_reorder' => route('listings.media.reorder', [
                'current_team' => $team->slug,
                'listing' => $listing->slug,
            ]),
        ];
    }
}
