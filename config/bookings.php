<?php

return [
    'per_page' => 15,
    'max_stay_days' => 30,
    'max_block_days' => 365,
    'expire_after_end_days' => (int) env('BOOKINGS_EXPIRE_AFTER_END_DAYS', 0),
    'reminder_days' => (int) env('BOOKINGS_REMINDER_DAYS', 3),
    'import_max_rows' => (int) env('BOOKINGS_IMPORT_MAX_ROWS', 1000),
    'public_calendar_days' => 90,
    'deposit_currency' => 'MXN',
    'deposit_methods' => [
        'cash' => 'Efectivo',
        'transfer' => 'Transferencia',
        'other' => 'Otro',
    ],
    'inquiry_statuses' => [
        'new' => 'Nueva',
        'contacted' => 'Contactada',
        'accepted' => 'Aceptada',
        'declined' => 'Rechazada',
        'expired' => 'Expirada',
        'cancelled' => 'Cancelada',
    ],
    'deposit_statuses' => [
        'pending' => 'Pendiente',
        'received' => 'Recibido',
        'refunded' => 'Reembolsado',
        'cancelled' => 'Cancelado',
    ],
];
