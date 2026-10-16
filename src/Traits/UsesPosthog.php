<?php

namespace QodeNL\LaravelPosthog\Traits;

use Closure;
use Exception;
use Illuminate\Support\Facades\Log;
use PostHog\PostHog;
use QodeNL\LaravelPosthog\PosthogClient;

trait UsesPosthog
{
    public function posthogInit(): void
    {
        if (PosthogClient::$initialized) {
            return;
        }

        try {
            PostHog::init(
                config('posthog.key'),
                $this->posthogOptions(),
                secretKey: config('posthog.feature_flags.evaluate_locally') ? config('posthog.secret_key') : null,
            );

            PosthogClient::$initialized = true;
        } catch (Exception $e) {
            Log::error('Posthog initialization failed: '.$e->getMessage());
        }
    }

    protected function posthogOptions(): array
    {
        $options = [
            'host' => config('posthog.host'),
            'timeout' => $this->posthogIntOption('posthog.client.timeout'),
            'max_queue_size' => $this->posthogIntOption('posthog.client.max_queue_size'),
            'batch_size' => $this->posthogIntOption('posthog.client.batch_size'),
            'flush_interval_seconds' => $this->posthogFloatOption('posthog.client.flush_interval_seconds'),
            'compress_request' => config('posthog.client.compress_request'),
            'consumer' => config('posthog.client.consumer'),
            'feature_flag_request_timeout_ms' => $this->posthogIntOption('posthog.feature_flags.request_timeout_ms'),
            'feature_flag_request_max_retries' => $this->posthogIntOption('posthog.feature_flags.request_max_retries'),
            'before_send' => $this->posthogBeforeSend(),
        ];

        $options = array_filter($options, fn ($value) => $value !== null);

        return array_merge($options, config('posthog.options') ?? []);
    }

    protected function posthogBeforeSend(): ?Closure
    {
        $filters = [];

        if (! (config('posthog.feature_flags.send_events') ?? true)) {
            $filters[] = fn (array $event): ?array => ($event['event'] ?? null) === '$feature_flag_called'
                ? null
                : $event;
        }

        $releaseId = config('posthog.release_id');
        if ($releaseId !== null && $releaseId !== '') {
            $filters[] = function (array $event) use ($releaseId): array {
                $event['properties']['$release_id'] ??= (string) $releaseId;

                return $event;
            };
        }

        if ($filters === []) {
            return null;
        }

        return function (array $event) use ($filters): ?array {
            foreach ($filters as $filter) {
                $event = $filter($event);

                if ($event === null) {
                    return null;
                }
            }

            return $event;
        };
    }

    private function posthogIntOption(string $key): ?int
    {
        $value = config($key);

        return $value === null || $value === '' ? null : (int) $value;
    }

    private function posthogFloatOption(string $key): ?float
    {
        $value = config($key);

        return $value === null || $value === '' ? null : (float) $value;
    }
}
