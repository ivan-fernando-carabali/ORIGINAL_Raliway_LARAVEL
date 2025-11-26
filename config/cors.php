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

    // Se obtiene del FRONTEND_URL del .env y se convierte en array
    'allowed_origins' => array_filter(array_map('trim', explode(',', env('FRONTEND_URL', '
        http://localhost,
        http://localhost:8100,
        http://127.0.0.1,
        https://localhost,
        capacitor://localhost,
        ionic://localhost
    ')))),

    // Patrones adicionales para permitir localhost, Android e Ionic
    'allowed_origins_patterns' => [
        '#^https?://localhost(:\d+)?$#',
        '#^https?://127\.0\.0\.1(:\d+)?$#',
        '#^capacitor://localhost$#',
        '#^ionic://localhost$#',
    ],

    'allowed_headers' => [
        '*'
    ],

    'exposed_headers' => [
        'Authorization'
    ],

    'max_age' => 0,

    'supports_credentials' => true,
];
