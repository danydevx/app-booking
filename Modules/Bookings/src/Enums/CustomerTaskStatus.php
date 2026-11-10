<?php

namespace Modules\Bookings\Enums;

enum CustomerTaskStatus: string
{
    case Open = 'open';
    case Done = 'done';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Pendiente',
            self::Done => 'Completada',
            self::Cancelled => 'Cancelada',
        };
    }
}
