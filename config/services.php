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
        'shared_secret' => env('WEBSITE_PLATFORM_SHARED_SECRET', ''),
    ],
    'bazaar' => [
        'base_url' => env('BAZAAR_BASE_URL', 'https://bazaar.hatchers.ai'),
    ],
    'servio' => [
        'base_url' => env('SERVIO_BASE_URL', 'https://servio.hatchers.ai'),
    ],
    'stripe' => [
        'secret' => env('STRIPE_SECRET', ''),
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY', ''),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
        'platform_fee_percent' => env('STRIPE_PLATFORM_FEE_PERCENT', 0),
        'platform_fee_fixed' => env('STRIPE_PLATFORM_FEE_FIXED', 0),
    ],
];
