<?php

namespace Modules\Listings\Actions;

use Illuminate\Support\Facades\Storage;
use Modules\Listings\Models\ListingMedia;

final class DeleteListingMedia
{
    public function handle(ListingMedia $media): void
    {
        Storage::disk($media->disk)->delete($media->path);

        $media->delete();
    }
}
