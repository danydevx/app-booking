<?php

return [
    'currency' => 'mxn',

    /*
    |--------------------------------------------------------------------------
    | Grace policy
    |--------------------------------------------------------------------------
    |
    | Paid access is kept while a subscription is active, trialing or within
    | Stripe's cancellation grace period. Past-due subscriptions keep paid
    | access for this many days after the last subscription update.
    |
    */

    'past_due_grace_days' => 3,

    'plans' => [
        'free' => [
            'name' => 'Gratis',
            'description' => 'Para probar la plataforma y publicar una terraza.',
            'stripe_price_id' => null,
            'price_cents' => 0,
            'interval' => 'month',
            'active' => true,
            'limits' => [
                'listings' => 1,
                'photos_per_listing' => 5,
                'videos_per_listing' => 0,
            ],
            'features' => [
                'customers' => false,
                'ai_posts' => false,
            ],
        ],

        'pro' => [
            'name' => 'Pro',
            'description' => 'Para anfitriones con varias terrazas y CRM de clientes.',
            'stripe_price_id' => env('STRIPE_PRICE_PRO'),
            'price_cents' => 49900,
            'interval' => 'month',
            'active' => true,
            'limits' => [
                'listings' => 5,
                'photos_per_listing' => 20,
                'videos_per_listing' => 3,
            ],
            'features' => [
                'customers' => true,
                'ai_posts' => false,
            ],
        ],
    ],

    'limit_labels' => [
        'listings' => 'Terrazas',
        'photos_per_listing' => 'Fotos por terraza',
        'videos_per_listing' => 'Videos por terraza',
    ],

    'feature_labels' => [
        'customers' => 'CRM de clientes',
        'ai_posts' => 'Publicaciones con IA',
    ],
];
