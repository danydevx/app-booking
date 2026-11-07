<?php

return [
    'terrace' => [
        'label' => 'Terraza',
        'services' => [
            'wifi' => 'Wi-Fi',
            'bathroom' => 'Baño',
            'kitchen' => 'Cocina',
            'parking' => 'Estacionamiento',
            'sound' => 'Sonido',
            'lighting' => 'Iluminación',
            'tables' => 'Mesas y sillas',
            'heating' => 'Calefacción',
            'air_conditioning' => 'Aire acondicionado',
            'pool' => 'Alberca',
            'grill' => 'Asador',
            'accessible' => 'Accesible',
        ],
        'details' => [
            'space_type' => [
                'label' => 'Tipo de espacio',
                'type' => 'select',
                'options' => [
                    'terrace' => 'Terraza',
                    'rooftop' => 'Rooftop',
                    'garden' => 'Jardín',
                    'patio' => 'Patio',
                    'interior' => 'Interior',
                ],
            ],
            'access' => [
                'label' => 'Acceso',
                'type' => 'text',
                'placeholder' => 'Ej. elevador, entrada independiente',
            ],
            'parking' => [
                'label' => 'Estacionamiento',
                'type' => 'text',
                'placeholder' => 'Ej. 5 cajones, no incluido',
            ],
            'view' => [
                'label' => 'Vista',
                'type' => 'text',
                'placeholder' => 'Ej. panorámica, ciudad',
            ],
        ],
    ],
];
