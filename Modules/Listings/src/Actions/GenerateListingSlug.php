<?php

namespace Modules\Listings\Actions;

use Illuminate\Support\Str;
use Modules\Listings\Models\Listing;

final class GenerateListingSlug
{
    public function handle(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'terraza';
        }

        $slug = $base;
        $suffix = 2;

        while (
            Listing::query()
                ->where('slug', $slug)
                ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
