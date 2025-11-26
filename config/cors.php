<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://localhost',
        'https://localhost:8100',
        'capacitor://localhost',
        'ionic://localhost',
        'http://localhost',
        'http://localhost:8100',
        'http://localhost:4200',
        'https://smartinventori-production.up.railway.app',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];

