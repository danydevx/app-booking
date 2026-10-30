<?php

namespace Modules\Bookings\Enums;

enum AvailabilityStatus: string
{
    case Blocked = 'blocked';
    case Held = 'held';
    case Booked = 'booked';

    public function label(): string
    {
        return match ($this) {
            self::Blocked => 'Bloqueada',
            self::Held => 'Apartada',
            self::Booked => 'Reservada',
        };
    }
}
