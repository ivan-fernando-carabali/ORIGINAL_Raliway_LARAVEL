<?php

return [

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'login',
        'logout',
        'user'
    ],

    'allowed_methods' => ['*'],

    // URLs permitidas desde FRONTEND_URL en .env
    'allowed_origins' => array_filter(array_map('trim', explode(',', env('FRONTEND_URL')))),

    // Patrones adicionales
    'allowed_origins_patterns' => [
        '#^https?://localhost(:\d+)?$#',
        '#^https?://127\.0\.0\.1(:\d+)?$#',
        '#^capacitor://localhost$#',
        '#^ionic://localhost$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Authorization'],

    'max_age' => 0,

    'supports_credentials' => true,
];
