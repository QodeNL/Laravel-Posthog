<?php

return [
    'enabled' => env('POSTHOG_ENABLED', true),
    'key'     => env('POSTHOG_KEY', ''),
    'host'    => env('POSTHOG_HOST', 'https://app.posthog.com'),
];
