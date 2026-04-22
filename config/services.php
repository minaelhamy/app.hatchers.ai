<?php

return [
    'os' => [
        'shared_secret' => env('WEBSITE_PLATFORM_SHARED_SECRET', ''),
    ],
    'atlas' => [
        'base_url' => env('ATLAS_BASE_URL', 'https://atlas.hatchers.ai'),
        'shared_secret' => env('WEBSITE_PLATFORM_SHARED_SECRET', ''),
    ],
    'lms' => [
        'base_url' => env('LMS_BASE_URL', 'https://lms.hatchers.ai'),
    ],
    'bazaar' => [
        'base_url' => env('BAZAAR_BASE_URL', 'https://bazaar.hatchers.ai'),
    ],
    'servio' => [
        'base_url' => env('SERVIO_BASE_URL', 'https://servio.hatchers.ai'),
    ],
];
