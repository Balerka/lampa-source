<?php

return [
    'paths' => ['api/*', 'health', 'openapi.json'],
    'allowed_methods' => ['*'],
    'allowed_origins' => config('lampa.cors_allowed_origins', ['*']),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
