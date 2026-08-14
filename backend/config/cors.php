<?php

return [
    'paths' => ['api/*', 'api/v1/auth/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => array_values(array_filter(explode(',', env('FRONTEND_ORIGINS', 'http://localhost:5173')))),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Accept', 'Content-Type', 'Origin', 'X-Requested-With', 'X-XSRF-TOKEN', 'X-Request-ID', 'X-Respondent-Token', 'Idempotency-Key', 'If-Match'],
    'exposed_headers' => ['X-Request-ID'],
    'max_age' => 600,
    'supports_credentials' => true,
];
