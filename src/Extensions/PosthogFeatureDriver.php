<?php

namespace QodeNL\LaravelPosthog\Extensions;

use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Laravel\Pennant\Contracts\Driver;
use Laravel\Pennant\Feature;
use PostHog\PostHog;
use QodeNL\LaravelPosthog\Exceptions\PosthogFeatureException;
use QodeNL\LaravelPosthog\Traits\UsesPosthog;

class PosthogFeatureDriver implements Driver
{
    use UsesPosthog;

    public function __construct()
    {
        $this->posthogInit();
    }

    /**
     * Define an initial feature flag state resolver.
     */
    public function define(string $feature, callable $resolver): void {}

    /**
     * Retrieve the names of all defined features.
     */
    public function defined(): array
    {
        return [];
    }

    /**
     * Get multiple feature flag values.
     */
    public function getAll(array $features): array
    {
        $results = collect($features)->map(function ($scopes, $feature) {
            return $this->get($feature, $scopes);
        });

        return $results->toArray();
    }

    /**
     * Retrieve a feature flag's value.
     */
    public function get(string $feature, mixed $scope): mixed
    {
        return collect($scope)
            ->map(function ($scope) {
                if ($scope instanceof Authenticatable) {
                    return config('posthog.user_prefix', 'user').':'.$scope->getAuthIdentifier();
                }
                if ($scope instanceof Model) {
                    return Feature::serializeScope($scope);
                }

                return null;
            })
            ->map(function ($scope) use ($feature) {
                if ($scope === null) {
                    return false;
                }

                try {
                    $value = Posthog::getFeatureFlag(
                        $feature,
                        $scope,
                        onlyEvaluateLocally: config('posthog.feature_flags.evaluate_locally') ?? false,
                        sendFeatureFlagEvents: config('posthog.feature_flags.send_events') ?? true,
                    );

                    return $value ?? false;
                } catch (Exception $e) {
                    return config('posthog.feature_flags.default_enabled') ?? false;
                }
            })
            ->toArray();
    }

    /**
     * Set a feature flag's value.
     *
     * @throws PosthogFeatureException
     */
    public function set(string $feature, mixed $scope, mixed $value): void
    {
        throw PosthogFeatureException::settingNotSupported();
    }

    /**
     * Set a feature flag's value for all scopes.
     *
     * @throws PosthogFeatureException
     */
    public function setForAllScopes(string $feature, mixed $value): void
    {
        throw PosthogFeatureException::settingNotSupported();
    }

    /**
     * Delete a feature flag's value.
     *
     * @throws PosthogFeatureException
     */
    public function delete(string $feature, mixed $scope): void
    {
        throw PosthogFeatureException::deletingNotSupported();
    }

    /**
     * Purge the given feature from storage.
     *
     * @throws PosthogFeatureException
     */
    public function purge(?array $features): void
    {
        throw PosthogFeatureException::purgingNotSupported();
    }
}
