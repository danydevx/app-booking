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
            $listing->media->each(
                fn ($media) => Storage::disk($media->disk)->delete($media->path),
            );

            $listing->delete();
        });
    }
}
