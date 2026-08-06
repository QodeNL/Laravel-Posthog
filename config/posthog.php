<?php

return [
    'enabled' => env('POSTHOG_ENABLED', true),
    'host' => env('POSTHOG_HOST', 'https://app.posthog.com'),
    'key' => env('POSTHOG_KEY', ''),

    // Only required when Feature Flag evaluate_locally is enabled
    'secret_key' => env('POSTHOG_SECRET_KEY'),

    'user_prefix' => 'user',

    'feature_flags' => [
        'default_enabled' => env('POSTHOG_FF_DEFAULT_ENABLED', false),
        'send_events' => env('POSTHOG_FF_SEND_EVENTS', true),
        'evaluate_locally' => env('POSTHOG_FF_EVALUATE_LOCALLY', false),
        'request_timeout_ms' => env('POSTHOG_FF_REQUEST_TIMEOUT_MS'),
        'request_max_retries' => env('POSTHOG_FF_REQUEST_MAX_RETRIES'),
    ],

    'client' => [
        'timeout' => env('POSTHOG_TIMEOUT'),                               // [10000]
        'max_queue_size' => env('POSTHOG_MAX_QUEUE_SIZE'),                 // [10000] events
        'batch_size' => env('POSTHOG_BATCH_SIZE'),                         // [100] events
        'flush_interval_seconds' => env('POSTHOG_FLUSH_INTERVAL_SECONDS'), // [5] seconds
        'compress_request' => env('POSTHOG_COMPRESS_REQUEST'),             // [false]
        'consumer' => env('POSTHOG_CONSUMER'),                             // [lib_curl]
    ],

    'options' => [],

    'queue' => [
        'connection' => env('POSTHOG_JOBS_CONNECTION', env('QUEUE_CONNECTION')),
        'queue' => env('POSTHOG_JOBS_QUEUE'),
    ],
];
