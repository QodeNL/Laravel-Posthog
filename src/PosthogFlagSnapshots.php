<?php

namespace QodeNL\LaravelPosthog;

use PostHog\FeatureFlagEvaluations;
use PostHog\PostHog;
use QodeNL\LaravelPosthog\Traits\UsesPosthog;

class PosthogFlagSnapshots
{
    use UsesPosthog;

    /** @var array<string, FeatureFlagEvaluations> */
    private array $snapshots = [];

    private bool $initialized = false;

    public function for(
        string $distinctId,
        array $groups = [],
        array $personProperties = [],
        array $groupProperties = [],
    ): FeatureFlagEvaluations {
        $key = md5(serialize([$distinctId, $groups, $personProperties, $groupProperties]));

        return $this->snapshots[$key] ??= $this->evaluate(
            $distinctId,
            $groups,
            $personProperties,
            $groupProperties,
        );
    }

    public function flush(): void
    {
        $this->snapshots = [];
    }

    private function evaluate(
        string $distinctId,
        array $groups,
        array $personProperties,
        array $groupProperties,
    ): FeatureFlagEvaluations {
        if (! $this->initialized) {
            $this->posthogInit();
            $this->initialized = true;
        }

        return PostHog::evaluateFlags(
            $distinctId,
            $groups,
            $personProperties,
            $groupProperties,
            config('posthog.feature_flags.evaluate_locally') ?? false,
        );
    }
}
