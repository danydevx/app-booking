<?php

namespace Modules\Listings\Actions;

use Modules\Listings\Models\ListingMedia;

final class UpdateListingMediaAltText
{
    public function handle(ListingMedia $media, ?string $altText): void
    {
        $altText = $altText !== null ? trim($altText) : null;

        $media->update([
            'alt_text' => $altText === '' ? null : $altText,
        ]);
    }
}
