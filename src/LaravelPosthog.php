<?php

namespace QodeNL\LaravelPosthog;

use Auth;
use Log;
use QodeNL\LaravelPosthog\Jobs\PosthogAliasJob;
use QodeNL\LaravelPosthog\Jobs\PosthogCaptureJob;
use QodeNL\LaravelPosthog\Jobs\PosthogIdentifyJob;

class LaravelPosthog
{

    protected string $sessionId;

    public function __construct()
    {
        $this->sessionId = Auth::user()
            ? config('posthog.user_prefix', 'user') . ':' . Auth::user()->id
            : sha1(session()->getId());
    }

    private function posthogEnabled(): bool
    {
        if (!config('posthog.enabled')) {
            return false;
        }

        return true;
    }

    public function identify(string $email, array $properties = []): void
    {
        if ($this->posthogEnabled()) {
            PosthogIdentifyJob::dispatch($this->sessionId, $email, $properties);
        } else {
            Log::debug('PosthogIdentifyJob not dispatched because posthog is disabled');
        }
    }

    public function capture(string $event, array $properties = []): void
    {
        if ($this->posthogEnabled()) {
            PosthogCaptureJob::dispatch($this->sessionId, $event, $properties);
        } else {
            Log::debug('PosthogCaptureJob not dispatched because posthog is disabled');
        }
    }

    public function alias(string $userId): void
    {
        if ($this->posthogEnabled()) {
            PosthogAliasJob::dispatch($this->sessionId, $userId);
        } else {
            Log::debug('PosthogAliasJob not dispatched because posthog is disabled');
        }
    }

}