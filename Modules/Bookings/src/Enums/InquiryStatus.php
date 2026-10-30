<?php

namespace Modules\Bookings\Enums;

enum InquiryStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nueva',
            self::Contacted => 'Contactada',
            self::Accepted => 'Aceptada',
            self::Declined => 'Rechazada',
            self::Expired => 'Expirada',
            self::Cancelled => 'Cancelada',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Declined, self::Expired, self::Cancelled], true);
    }
}
