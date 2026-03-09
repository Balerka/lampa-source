<?php

return [
    'device_code_ttl' => (int) env('LAMPA_DEVICE_CODE_TTL', 300),
    'premium_until' => env('LAMPA_PREMIUM_UNTIL', '2099-12-31T00:00:00.000Z'),
    'manual_device_code_secret' => env('MANUAL_DEVICE_CODE_SECRET'),
    'api_rate_limit_per_minute' => (int) env('LAMPA_API_RATE_LIMIT_PER_MINUTE', 120),
    'cors_allowed_origins' => array_values(array_filter(array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', '*'))))),
    'timeline_socket' => [
        'host' => env('TIMELINE_SOCKET_HOST', '127.0.0.1'),
        'port' => (int) env('TIMELINE_SOCKET_PORT', 9001),
    ],
];
