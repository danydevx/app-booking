<?php

return [
    'default_type' => 'terrace',

    'default_timezone' => 'America/Mexico_City',

    'currency' => 'MXN',

    'geocoder' => 'null',

    'directory' => [
        'team_name' => env('LISTINGS_DIRECTORY_TEAM_NAME', 'Directorio'),
    ],

    'claims' => [
        'evidence_disk' => 'local',
        'max_evidence_kb' => 10240,
        'evidence_mimes' => ['pdf', 'jpg', 'jpeg', 'png', 'webp'],
    ],

    'media' => [
        'disk' => 'public',
        'max_images' => 20,
        'max_videos' => 3,
        'max_image_kb' => 5120,
        'max_video_kb' => 51200,
        'image_mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'image_mimetypes' => ['image/jpeg', 'image/png', 'image/webp'],
        'video_mimes' => ['mp4', 'mov', 'webm'],
        'video_mimetypes' => ['video/mp4', 'video/quicktime', 'video/webm'],
    ],

    'search' => [
        'per_page' => 12,
        'default_radius_km' => 25,
        'max_radius_km' => 100,
        'max_candidates' => 500,
        'map_markers' => 300,
        'suggestions_cache_seconds' => 60,
    ],
];
