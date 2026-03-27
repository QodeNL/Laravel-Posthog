<?php

namespace QodeNL\LaravelPosthog\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use PostHog\PostHog;
use QodeNL\LaravelPosthog\Traits\UsesPosthog;

class PosthogCaptureJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use UsesPosthog;

    public function __construct(
        private string $sessionId,
        private string $event,
        private array $properties = [],
        private null|string|int|float $timestamp = null,
        private array $groups = []
    ) {
        if (config('posthog.queue.connection')) {
            $this->onConnection(config('posthog.queue.connection'));
        }

        if (config('posthog.queue.queue')) {
            $this->onQueue(config('posthog.queue.queue', 'default'));
        }
    }

    public function handle(): void
    {
        $this->posthogInit();

        try {
            $payload = [
                'distinctId' => $this->sessionId,
                'event' => $this->event,
                'properties' => $this->properties,
                'timestamp' => $this->timestamp,
            ];

            if (! empty($this->groups)) {
                $payload['$groups'] = $this->groups;
            }

            PostHog::capture($payload);
        } catch (Exception $e) {
            Log::info('Posthog capture call failed:'.$e->getMessage());
        }
    }
}
