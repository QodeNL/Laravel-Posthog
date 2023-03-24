<?php

namespace QodeNL\LaravelPosthog\Jobs;

use Exception;
use Illuminate\Support\Facades\Log;
use PostHog\PostHog;

class PosthogBaseJob
{
    public function init(): void
    {
        try {
            PostHog::init(config('posthog.key'),
                ['host' => config('posthog.host')]
            );
        } catch (Exception $e) {
            Log::error('Posthog initialization failed: ' . $e->getMessage());
        }
    }
}