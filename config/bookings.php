<?php

return [
    'per_page' => 15,
    'max_stay_days' => 30,
    'expire_after_end_days' => (int) env('BOOKINGS_EXPIRE_AFTER_END_DAYS', 0),
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
