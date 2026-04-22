<?php

return [
    'atlas' => [
        'name' => 'Atlas',
        'base_url' => env('ATLAS_BASE_URL', 'https://atlas.hatchers.ai'),
        'role' => 'shared intelligence and AI assistant engine',
    ],
    'lms' => [
        'name' => 'LMS',
        'base_url' => env('LMS_BASE_URL', 'https://lms.hatchers.ai'),
        'role' => 'mentoring, tasks, milestones, and meetings',
    ],
    'bazaar' => [
        'name' => 'Bazaar',
        'base_url' => env('BAZAAR_BASE_URL', 'https://bazaar.hatchers.ai'),
        'role' => 'ecommerce engine for product businesses',
    ],
    'servio' => [
        'name' => 'Servio',
        'base_url' => env('SERVIO_BASE_URL', 'https://servio.hatchers.ai'),
        'role' => 'service and bookings engine',
    ],
];
