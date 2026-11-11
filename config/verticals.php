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
                    'accessible' => 'Acceso para personas con discapacidad',
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
                    'sound' => 'Equipo de sonido',
                    'lighting' => 'Iluminación',
                    'smart_tv' => 'Smart TV',
                    'heating' => 'Calefacción',
                    'air_conditioning' => 'Aire acondicionado',
                ],
            ],
            'entertainment' => [
                'label' => 'Entretenimiento e instalaciones recreativas',
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
                    'rooms' => 'Habitaciones',
                    'lodging' => 'Área de hospedaje',
                ],
            ],
            'security' => [
                'label' => 'Seguridad',
                'items' => [
                    'security_cameras' => 'Cámaras de seguridad',
                ],
            ],
        ],
        'commercial_services' => [
            'food' => [
                'label' => 'Alimentos y bebidas',
                'items' => [
                    'catering' => 'Banquetes',
                    'taquizas' => 'Taquizas',
                    'full_catering' => 'Servicio de catering',
                    'drinks_bar' => 'Barra de bebidas',
                    'bartender' => 'Bartender',
                    'ice_and_drinks' => 'Hielo y refrescos',
                    'candy_bar' => 'Candy bar',
                    'dessert_table' => 'Mesa de postres',
                ],
            ],
            'decoration' => [
                'label' => 'Decoración y ambientación',
                'items' => [
                    'decoration' => 'Decoración para eventos',
                    'themed_decoration' => 'Decoración temática',
                    'floral_arrangements' => 'Arreglos florales',
                    'table_setup' => 'Montaje de mesas',
                    'centerpieces' => 'Centros de mesa',
                    'balloon_decoration' => 'Decoración con globos',
                ],
            ],
            'music' => [
                'label' => 'Música y entretenimiento',
                'items' => [
                    'dj' => 'DJ',
                    'live_music' => 'Música en vivo',
                    'entertainers' => 'Animadores',
                    'kids_shows' => 'Shows infantiles',
                    'karaoke' => 'Karaoke',
                    'bounce_house_rental' => 'Renta de brincolines',
                ],
            ],
            'staff' => [
                'label' => 'Personal y atención',
                'items' => [
                    'waiters' => 'Meseros',
                    'cleaning_staff' => 'Personal de limpieza',
                    'security_staff' => 'Personal de seguridad',
                    'event_coordinator' => 'Coordinador de eventos',
                    'kitchen_staff' => 'Personal de cocina',
                ],
            ],
            'equipment' => [
                'label' => 'Equipamiento adicional',
                'items' => [
                    'furniture_rental' => 'Renta de mobiliario',
                    'linen_rental' => 'Renta de mantelería',
                    'sound_rental' => 'Renta de equipo de sonido',
                    'lighting_rental' => 'Renta de iluminación',
                    'av_rental' => 'Renta de proyectores y pantallas',
                    'tent_rental' => 'Renta de carpas',
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
