<?php

return [
    'paths' => ['api/*', 'auth/*', 'sanctum/csrf-cookie', 'admin/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['https://www.korecha.com.et', 'https://admin.korecha.com.et', 'http://localhost:8080', 'http://localhost:5173'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
