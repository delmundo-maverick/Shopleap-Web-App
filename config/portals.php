<?php

return [
    'session_cookie' => env('SESSION_COOKIE', 'shopleap_session_v2'),
    'main' => rtrim(env('MAIN_BASE_URL', env('APP_URL', 'http://localhost:8000')), '/'),
    'logistics' => rtrim(env('LOGISTICS_BASE_URL', 'http://logistics.localhost:8000'), '/'),
    'admin' => rtrim(env('ADMIN_BASE_URL', env('MAIN_BASE_URL', env('APP_URL', 'http://localhost:8000'))), '/'),
    'additional_hosts' => array_filter(array_map('trim', explode(',', env('ADDITIONAL_ALLOWED_HOSTS', '')))),
    'force_https' => env('APP_ENV') === 'production' || env('PORTAL_FORCE_HTTPS', false),
    'support_email' => env('LOGISTICS_SUPPORT_EMAIL', env('MAIL_FROM_ADDRESS')),
];
