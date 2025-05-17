<?php

return [
    'paths' => ['*', 'api/*', 'sanctum/csrf-cookie', '/@vite/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*',
        'https://7d6a-57-135-226-79.ngrok-free.app',
        'https://samplepal_leads.test:5173'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['*'],

    'max_age' => 0,

    'supports_credentials' => true,
];
