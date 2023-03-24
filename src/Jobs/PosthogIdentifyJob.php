<?php

namespace QodeNL\LaravelPosthog\Jobs;

use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use PostHog\PostHog;

class PosthogIdentifyJob extends PosthogBaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private string $sessionId, private string $email, private array $properties = [])
    {
    }

    public function handle(): void
    {
        $this->init();

        try {
            Posthog::identify([
                'distinctId' => $this->sessionId,
                'properties' => ['email' => $this->email] + $this->properties,
            ]);
        } catch (Exception $e) {
            Log::info('Posthog identify call failed:' . $e->getMessage());
        }
    }

}