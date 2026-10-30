<?php

namespace Modules\Listings\Enums;

enum ListingStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Published => 'Publicada',
            self::Suspended => 'Suspendida',
        };
    }

    public function isPublic(): bool
    {
        return $this === self::Published;
    }
}
