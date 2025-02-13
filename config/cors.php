<?php

return [
    'paths' => ['api/*', 'auth/*', 'sanctum/csrf-cookie', 'admin/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:8080', 'http://localhost:5174', 'https://admin.korecha.com.et'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
