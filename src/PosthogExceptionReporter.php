<?php

namespace QodeNL\LaravelPosthog;

use PostHog\PostHog;
use QodeNL\LaravelPosthog\Traits\UsesPosthog;
use Throwable;

class PosthogExceptionReporter
{
    use UsesPosthog;

    public function __invoke(Throwable $exception): void
    {
        try {
            app('LaravelPosthog')->captureException($exception);
        } catch (Throwable) {
            // Resolving the distinct ID can fail for the same reason the app did (e.g. the database is down).
            $this->captureAnonymously($exception);
        }
    }

    private function captureAnonymously(Throwable $exception): void
    {
        if (! config('posthog.enabled') || config('posthog.key') === '') {
            return;
        }

        try {
            $this->posthogInit();

            PostHog::captureException($exception);
        } catch (Throwable) {
            // Never let reporting to Posthog break Laravel's own exception handling.
        }
    }
}
