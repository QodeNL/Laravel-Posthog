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

    public function __construct(private string $sessionId, private string $event, private array $properties = []) {}

    public function handle(): void
    {
        $this->posthogInit();

        try {
            Posthog::capture([
                'distinctId' => $this->sessionId,
                'event' => $this->event,
                'properties' => $this->properties,
            ]);
        } catch (Exception $e) {
            Log::info('Posthog capture call failed:'.$e->getMessage());
        }
    }
}
