<?php

namespace QodeNL\LaravelPosthog\Tests;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Orchestra\Testbench\Attributes\DefineEnvironment;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use QodeNL\LaravelPosthog\PosthogExceptionReporter;
use RuntimeException;

class PosthogExceptionReporterTest extends TestCase
{
    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function it_captures_the_exception_for_the_current_distinct_id(): void
    {
        $exception = new RuntimeException('boom');

        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->once();
        $mock->shouldReceive('captureException')->once()->with($exception, Mockery::type('string'), [])->andReturnTrue();

        Auth::shouldReceive('user')->andReturnNull();

        (new PosthogExceptionReporter)($exception);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function it_captures_anonymously_when_the_distinct_id_cannot_be_resolved(): void
    {
        $exception = new RuntimeException('boom');

        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->once();
        $mock->shouldReceive('captureException')->once()->with($exception)->andReturnTrue();

        Auth::shouldReceive('user')->andThrow(new RuntimeException('Database is down'));

        (new PosthogExceptionReporter)($exception);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function it_never_throws_when_the_sdk_fails(): void
    {
        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->andThrow(new \Error('SDK broken'));
        $mock->shouldNotReceive('captureException');

        Auth::shouldReceive('user')->andThrow(new RuntimeException('Database is down'));

        (new PosthogExceptionReporter)(new RuntimeException('boom'));

        $this->addToAssertionCount(1);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function the_fallback_does_nothing_when_posthog_is_disabled(): void
    {
        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldNotReceive('init');
        $mock->shouldNotReceive('captureException');

        config()->set('posthog.enabled', false);
        Auth::shouldReceive('user')->andThrow(new RuntimeException('Database is down'));

        (new PosthogExceptionReporter)(new RuntimeException('boom'));
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    #[DefineEnvironment('enableErrorTracking')]
    public function reported_exceptions_reach_posthog_when_error_tracking_is_enabled(): void
    {
        $exception = new RuntimeException('boom');

        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->once();
        $mock->shouldReceive('captureException')->once()->with($exception, Mockery::type('string'), [])->andReturnTrue();

        Auth::shouldReceive('user')->andReturnNull();

        report($exception);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    #[DefineEnvironment('enableErrorTracking')]
    public function exceptions_in_dont_report_are_not_sent(): void
    {
        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldNotReceive('captureException');

        $this->app->make(ExceptionHandler::class)->dontReport(RuntimeException::class);

        report(new RuntimeException('boom'));
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function reported_exceptions_are_not_sent_when_error_tracking_is_disabled(): void
    {
        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldNotReceive('captureException');

        report(new RuntimeException('boom'));
    }

    protected function enableErrorTracking($app): void
    {
        $app['config']->set('posthog.error_tracking.enabled', true);
    }
}
