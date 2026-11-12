<?php

namespace Modules\Listings\Actions;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Modules\Listings\Models\ListingMedia;
use RuntimeException;

final class GenerateListingMediaThumbnail
{
    public const SIZE = 400;

    public const QUALITY = 85;

    /**
     * Generate the 400x400 WebP thumbnail without touching the original.
     * Existing thumbnails are left untouched unless $force is true.
     */
    public function handle(ListingMedia $media, bool $force = false): ?string
    {
        $thumbnailPath = $media->thumbnailPath();

        if ($thumbnailPath === null) {
            return null;
        }

        $disk = Storage::disk($media->disk);

        if (! $disk->exists($media->path)) {
            throw new RuntimeException(
                "The original image [{$media->path}] does not exist on disk [{$media->disk}].",
            );
        }

        if (! $force && $disk->exists($thumbnailPath)) {
            return $thumbnailPath;
        }

        $image = ImageManager::gd()
            ->read($disk->get($media->path))
            ->orient()
            ->cover(self::SIZE, self::SIZE);

        $encoded = $image->toWebp(self::QUALITY);

        $disk->put($thumbnailPath, $encoded->toString());

        return $thumbnailPath;
    }
}
