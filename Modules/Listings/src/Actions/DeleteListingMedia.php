<?php

namespace Modules\Listings\Actions;

use Illuminate\Support\Facades\Storage;
use Modules\Listings\Models\ListingMedia;

final class DeleteListingMedia
{
    public function handle(ListingMedia $media): void
    {
        $disk = Storage::disk($media->disk);
        $thumbnailPath = $media->thumbnailPath();

        $disk->delete($media->path);

        if ($thumbnailPath !== null) {
            $disk->delete($thumbnailPath);
        }

        $media->delete();
    }
}
