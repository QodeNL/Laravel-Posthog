<?php

namespace QodeNL\LaravelPosthog\Tests\Traits;

use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use QodeNL\LaravelPosthog\Tests\TestCase;
use QodeNL\LaravelPosthog\Traits\UsesPosthog;

class UsesPosthogTest extends TestCase
{
    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function posthog_init_calls_sdk_with_config(): void
    {
        config()->set('posthog.key', 'test-api-key');
        config()->set('posthog.host', 'https://test.posthog.com');

        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')
            ->once()
            ->with('test-api-key', ['host' => 'https://test.posthog.com']);

        $instance = new class
        {
            use UsesPosthog;
        };

        $instance->posthogInit();
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function posthog_init_catches_exception_and_logs_error(): void
    {
        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->once()->andThrow(new \Exception('Init failed'));

        Log::shouldReceive('error')->once()->with('Posthog initialization failed: Init failed');

        $instance = new class
        {
            use UsesPosthog;
        };

        $instance->posthogInit();
    }
}
