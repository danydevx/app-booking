<?php

return [
    'per_page' => 15,
    'max_stay_days' => 30,
    'max_block_days' => 365,
    'expire_after_end_days' => (int) env('BOOKINGS_EXPIRE_AFTER_END_DAYS', 0),
    'reminder_days' => (int) env('BOOKINGS_REMINDER_DAYS', 3),
    'import_max_rows' => (int) env('BOOKINGS_IMPORT_MAX_ROWS', 1000),
    'availability_recurrence_horizon_days' => (int) env('BOOKINGS_AVAILABILITY_RECURRENCE_HORIZON_DAYS', 366),
    'public_calendar_days' => 90,
    'deposit_currency' => 'MXN',

    /*
    |--------------------------------------------------------------------------
    | Cotizaciones comerciales (Plan Pro)
    |--------------------------------------------------------------------------
    |
    | La tasa de IVA está centralizada para poder ajustarla sin tocar el motor
    | de cálculos. Los importes siempre se guardan en centavos.
    |
    */
    'quotes' => [
        'currency' => 'MXN',
        'tax_rate_bps' => (int) env('BOOKINGS_QUOTE_TAX_RATE_BPS', 1600),
        'valid_days' => (int) env('BOOKINGS_QUOTE_VALID_DAYS', 15),
        'max_valid_days' => (int) env('BOOKINGS_QUOTE_MAX_VALID_DAYS', 365),
        'pdf_disk' => env('BOOKINGS_QUOTE_PDF_DISK', 'local'),
        'paper' => env('BOOKINGS_QUOTE_PAPER', 'letter'),
    ],
    'deposit_methods' => [
        'cash' => 'Efectivo',
        'transfer' => 'Transferencia',
        'other' => 'Otro',
    ],
    'contact_guest_ranges' => [
        '1-50' => '1–50',
        '51-100' => '51–100',
        '101-150' => '101–150',
        '150+' => 'Más de 150',
    ],
    'contact_dedupe_minutes' => 10,
    'contact_default_message' => 'Hola, me gustaría recibir más información sobre esta terraza y conocer su disponibilidad para mi evento.',
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
