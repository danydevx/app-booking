<?php

namespace Modules\Bookings\Enums;

enum DepositMethod: string
{
    case Cash = 'cash';
    case Transfer = 'transfer';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Efectivo',
            self::Transfer => 'Transferencia',
            self::Other => 'Otro',
        };
    }
}
