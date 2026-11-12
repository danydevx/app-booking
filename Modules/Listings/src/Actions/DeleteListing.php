<?php

namespace Modules\Listings\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Listings\Models\Listing;

final class DeleteListing
{
    public function handle(Listing $listing): void
    {
        DB::transaction(function () use ($listing): void {
            $listing->media->each(function ($media): void {
                $disk = Storage::disk($media->disk);
                $thumbnailPath = $media->thumbnailPath();

                $disk->delete($media->path);

                if ($thumbnailPath !== null) {
                    $disk->delete($thumbnailPath);
                }
            });

            if (filled($listing->logo_path)) {
                Storage::disk((string) config('listings.media.disk', 'public'))
                    ->delete((string) $listing->logo_path);
            }

            $listing->delete();
        });
    }
}
