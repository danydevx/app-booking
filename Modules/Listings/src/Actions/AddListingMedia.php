<?php

namespace Modules\Listings\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Modules\Listings\Enums\ListingMediaType;
use Modules\Listings\Models\Listing;
use Modules\Listings\Models\ListingMedia;
use RuntimeException;

final class AddListingMedia
{
    public function handle(
        Listing $listing,
        UploadedFile $file,
        ListingMediaType $type,
        ?string $altText = null,
    ): ListingMedia {
        $this->ensureWithinLimits($listing, $type);

        $disk = (string) config('listings.media.disk', 'public');
        $path = $file->store("listings/{$listing->id}", $disk);

        if (! is_string($path)) {
            throw new RuntimeException('The media file could not be stored.');
        }

        $width = null;
        $height = null;

        if ($type === ListingMediaType::Image) {
            $dimensions = @getimagesize($file->getRealPath());

            if ($dimensions !== false) {
                $width = $dimensions[0];
                $height = $dimensions[1];
            }
        }

        $sortOrder = (int) $listing->media()->max('sort_order') + 1;

        return $listing->media()->create([
            'type' => $type,
            'disk' => $disk,
            'path' => $path,
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'size_bytes' => (int) $file->getSize(),
            'width' => $width,
            'height' => $height,
            'duration_seconds' => null,
            'alt_text' => $altText,
            'sort_order' => $sortOrder,
        ]);
    }

    private function ensureWithinLimits(Listing $listing, ListingMediaType $type): void
    {
        $max = $type === ListingMediaType::Image
            ? (int) config('listings.media.max_images', 20)
            : (int) config('listings.media.max_videos', 3);

        $current = $type === ListingMediaType::Image
            ? $listing->images()->count()
            : $listing->videos()->count();

        if ($current >= $max) {
            throw ValidationException::withMessages([
                'file' => $type === ListingMediaType::Image
                    ? "La cuenta ya tiene el máximo de {$max} imágenes por terraza."
                    : "La cuenta ya tiene el máximo de {$max} videos por terraza.",
            ]);
        }
    }
}
