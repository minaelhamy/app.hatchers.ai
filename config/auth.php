<?php

return [
    'defaults' => [
        'guard' => 'web',
        'passwords' => 'founders',
    ],
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'founders',
        ],
    ],
    'providers' => [
        'founders' => [
            'driver' => 'eloquent',
            'model' => App\Models\Founder::class,
        ],
    ],
    'passwords' => [
        'founders' => [
            'provider' => 'founders',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],
    'password_timeout' => 10800,
];
