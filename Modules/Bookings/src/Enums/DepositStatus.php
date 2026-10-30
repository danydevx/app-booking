<?php

namespace Modules\Bookings\Enums;

enum DepositStatus: string
{
    case Pending = 'pending';
    case Received = 'received';
    case Refunded = 'refunded';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Received => 'Recibido',
            self::Refunded => 'Reembolsado',
            self::Cancelled => 'Cancelado',
        };
    }
}
