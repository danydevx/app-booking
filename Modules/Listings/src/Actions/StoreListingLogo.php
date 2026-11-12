<?php

namespace Modules\Listings\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Listings\Models\Listing;

final class StoreListingLogo
{
    public function handle(
        Listing $listing,
        ?UploadedFile $logo,
        bool $remove = false,
    ): void {
        $disk = (string) config('listings.media.disk', 'public');
        $previous = $listing->logo_path;

        if ($logo === null && ! $remove) {
            return;
        }

        if ($logo === null) {
            if (filled($previous)) {
                Storage::disk($disk)->delete((string) $previous);
            }

            $listing->forceFill(['logo_path' => null])->save();

            return;
        }

        $extension = $logo->extension() ?: $logo->guessExtension() ?: 'png';
        $path = 'listings/'.$listing->id.'/logo-'.Str::random(16).'.'.$extension;

        Storage::disk($disk)->putFileAs(
            'listings/'.$listing->id,
            $logo,
            basename($path),
        );

        $listing->forceFill(['logo_path' => $path])->save();

        if (filled($previous) && $previous !== $path) {
            Storage::disk($disk)->delete((string) $previous);
        }
    }
}
