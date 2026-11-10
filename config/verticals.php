<?php

return [
    'terrace' => [
        'label' => 'Terraza',
        'services' => [
            'spaces' => [
                'label' => 'Instalaciones y espacios',
                'items' => [
                    'wifi' => 'Wi-Fi',
                    'bathroom' => 'Baños',
                    'parking' => 'Estacionamiento',
                    'accessible' => 'Accesible',
                    'covered_terrace' => 'Terraza techada',
                    'green_areas' => 'Áreas verdes',
                    'garden' => 'Jardín',
                    'bar' => 'Barra',
                    'dining_room' => 'Comedor',
                    'living_room' => 'Sala de estar',
                    'rooftop' => 'Rooftop',
                    'pergola' => 'Pérgola',
                ],
            ],
            'pools' => [
                'label' => 'Albercas y descanso',
                'items' => [
                    'pool' => 'Alberca',
                    'heated_pool' => 'Alberca climatizada',
                    'paddling_pool' => 'Chapoteadero',
                    'jacuzzi' => 'Jacuzzi',
                    'lounge_chairs' => 'Camastros',
                    'showers' => 'Regaderas',
                ],
            ],
            'kitchen' => [
                'label' => 'Cocina y alimentos',
                'items' => [
                    'kitchen' => 'Cocina',
                    'equipped_kitchen' => 'Cocina equipada',
                    'grill' => 'Asador',
                    'refrigerator' => 'Refrigerador',
                    'stove' => 'Estufa',
                    'griddle' => 'Plancha para cocinar',
                    'cooler' => 'Hielera',
                ],
            ],
            'furniture' => [
                'label' => 'Mobiliario y equipamiento',
                'items' => [
                    'tables' => 'Mesas y sillas',
                    'kids_furniture' => 'Mobiliario infantil',
                    'table_linen' => 'Mantelería',
                    'sound' => 'Sonido',
                    'lighting' => 'Iluminación',
                    'smart_tv' => 'Smart TV',
                    'heating' => 'Calefacción',
                    'air_conditioning' => 'Aire acondicionado',
                ],
            ],
            'entertainment' => [
                'label' => 'Entretenimiento',
                'items' => [
                    'bounce_house' => 'Brincolín',
                    'foosball' => 'Futbolito',
                    'soccer_field' => 'Cancha de fútbol',
                    'pinata_area' => 'Piñatero',
                    'playground' => 'Juegos infantiles',
                    'frog_game' => 'Cajón ranita',
                    'fire_pit' => 'Fogatero',
                ],
            ],
            'lodging' => [
                'label' => 'Hospedaje',
                'items' => [
                    'lodging' => 'Hospedaje',
                    'rooms' => 'Habitaciones',
                ],
            ],
            'extras' => [
                'label' => 'Servicios adicionales',
                'items' => [
                    'catering' => 'Banquetes',
                    'taquizas' => 'Taquizas',
                    'decoration' => 'Decoración',
                    'candy_bar' => 'Candy bar',
                    'dj' => 'DJ',
                    'waiters' => 'Meseros',
                    'ice_and_drinks' => 'Hielo y refrescos',
                ],
            ],
            'security' => [
                'label' => 'Seguridad',
                'items' => [
                    'security_cameras' => 'Cámaras de seguridad',
                ],
            ],
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
