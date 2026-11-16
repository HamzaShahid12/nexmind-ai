<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    // Comma-separated list of SPA origins, e.g. http://localhost:5173
    'allowed_origins' => array_filter(array_map('trim', explode(',', env('FRONTEND_URL', 'http://localhost:5173')))),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    // Required for cookie-based Sanctum SPA authentication.
    'supports_credentials' => true,
];
