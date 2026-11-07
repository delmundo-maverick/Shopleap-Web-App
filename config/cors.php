<?php

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => array_values(array_unique([
        rtrim(env('MAIN_BASE_URL', env('APP_URL', 'http://localhost:8000')), '/'),
        rtrim(env('LOGISTICS_BASE_URL', 'http://logistics.localhost:8000'), '/'),
        rtrim(env('ADMIN_BASE_URL', env('MAIN_BASE_URL', env('APP_URL', 'http://localhost:8000'))), '/'),
    ])),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Content-Type', 'Authorization', 'Accept', 'X-Requested-With'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
