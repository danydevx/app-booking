<?php

namespace Modules\Listings\Enums;

enum ListingMediaType: string
{
    case Image = 'image';
    case Video = 'video';

    public function label(): string
    {
        return match ($this) {
            self::Image => 'Imagen',
            self::Video => 'Video',
        };
    }
}
