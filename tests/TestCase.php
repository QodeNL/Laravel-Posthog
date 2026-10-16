<?php

namespace QodeNL\LaravelPosthog\Tests;

use Mockery;
use Orchestra\Testbench\TestCase as BaseTestCase;
use PostHog\FeatureFlagEvaluations;
use QodeNL\LaravelPosthog\Facades\Posthog;
use QodeNL\LaravelPosthog\LaravelPosthog;
use QodeNL\LaravelPosthog\PosthogClient;
use QodeNL\LaravelPosthog\PosthogServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function fakeFlagSnapshot(array $flags, array $payloads = []): FeatureFlagEvaluations
    {
        $snapshot = Mockery::mock(FeatureFlagEvaluations::class);
        $snapshot->shouldReceive('getFlag')->andReturnUsing(fn (string $key) => $flags[$key] ?? null);
        $snapshot->shouldReceive('getFlagPayload')->andReturnUsing(fn (string $key) => $payloads[$key] ?? null);

        return $snapshot;
    }

    protected function getPackageProviders($app): array
    {
        return [
            PosthogServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Posthog' => Posthog::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('posthog.enabled', true);
        $app['config']->set('posthog.key', 'test-key-123');
        $app['config']->set('posthog.host', 'https://test.posthog.com');
        $app['config']->set('posthog.user_prefix', 'user');
        $app['config']->set('posthog.feature_flags.default_enabled', false);
        $app['config']->set('posthog.feature_flags.send_events', true);
        $app['config']->set('posthog.feature_flags.evaluate_locally', false);
    }

    protected function setUp(): void
    {
        PosthogClient::reset();

        parent::setUp();
    }

    protected function tearDown(): void
    {
        LaravelPosthog::resolveDistinctIdUsing(null);
        LaravelPosthog::resolveIpUsing(null);
        PosthogClient::reset();

        parent::tearDown();
    }
}
