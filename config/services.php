<?php

return [
    'os' => [
        'shared_secret' => env('WEBSITE_PLATFORM_SHARED_SECRET', env('HATCHERS_SHARED_SECRET', '')),
    ],
    'atlas' => [
        'base_url' => env('ATLAS_BASE_URL', 'https://atlas.hatchers.ai'),
        'shared_secret' => env('WEBSITE_PLATFORM_SHARED_SECRET', env('HATCHERS_SHARED_SECRET', '')),
    ],
    'lms' => [
        'base_url' => env('LMS_BASE_URL', 'https://lms.hatchers.ai'),
        'shared_secret' => env('WEBSITE_PLATFORM_SHARED_SECRET', env('HATCHERS_SHARED_SECRET', '')),
    ],
    'bazaar' => [
        'base_url' => env('BAZAAR_BASE_URL', 'https://bazaar.hatchers.ai'),
    ],
    'servio' => [
        'base_url' => env('SERVIO_BASE_URL', 'https://servio.hatchers.ai'),
    ],
    'stock_media' => [
        'unsplash_access_key' => env('UNSPLASH_ACCESS_KEY', ''),
        'pexels_api_key' => env('PEXELS_API_KEY', ''),
        'pixabay_api_key' => env('PIXABAY_API_KEY', ''),
    ],
    'openai' => [
        'api_key' => env('OPENAI_API_KEY', ''),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'website_copy_model' => env('OPENAI_WEBSITE_COPY_MODEL', 'gpt-4o-mini'),
    ],
    'stripe' => [
        'secret' => env('STRIPE_SECRET', ''),
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY', ''),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
        'platform_fee_percent' => env('STRIPE_PLATFORM_FEE_PERCENT', 0),
        'platform_fee_fixed' => env('STRIPE_PLATFORM_FEE_FIXED', 0),
    ],
];
