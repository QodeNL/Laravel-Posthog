<?php

namespace QodeNL\LaravelPosthog\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use QodeNL\LaravelPosthog\Facades\Posthog;
use QodeNL\LaravelPosthog\LaravelPosthog;
use QodeNL\LaravelPosthog\PosthogServiceProvider;

abstract class TestCase extends BaseTestCase
{
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

    protected function tearDown(): void
    {
        LaravelPosthog::resolveDistinctIdUsing(null);

        parent::tearDown();
    }
}
