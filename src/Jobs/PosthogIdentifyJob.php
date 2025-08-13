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

class PosthogIdentifyJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use UsesPosthog;

    public function __construct(private string $sessionId, private string $email, private array $properties = [], private null|string|int|float $timestamp = null) {}

    public function handle(): void
    {
        $this->posthogInit();

        try {
            Posthog::identify([
                'distinctId' => $this->sessionId,
                'properties' => ['email' => $this->email] + $this->properties,
                'timestamp' => $this->timestamp,
            ]);
        } catch (Exception $e) {
            Log::info('Posthog identify call failed:'.$e->getMessage());
        }
    }
}
