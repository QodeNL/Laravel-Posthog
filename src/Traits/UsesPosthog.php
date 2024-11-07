<?php

namespace QodeNL\LaravelPosthog\Traits;

use Illuminate\Support\Facades\Log;
use PostHog\PostHog;

trait UsesPosthog
{
    public function posthogInit(): void
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