<?php

return [
    'enabled'     => env('POSTHOG_ENABLED', true),
    'host'        => env('POSTHOG_HOST', 'https://app.posthog.com'),
    'key'         => env('POSTHOG_KEY', ''),
    'user_prefix' => 'user',
];
