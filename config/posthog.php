<?php

return [
    'enabled' => env('POSTHOG_ENABLED', true),
    'host' => env('POSTHOG_HOST', 'https://app.posthog.com'),
    'key' => env('POSTHOG_KEY', ''),
    'user_prefix' => 'user',

    'feature_flags' => [
        'default_enabled' => env('POSTHOG_FF_DEFAULT_ENABLED', false),
        'send_events' => env('POSTHOG_FF_SEND_EVENTS', true),
        'evaluate_locally' => env('POSTHOG_FF_EVALUATE_LOCALLY', false),
    ],
];
