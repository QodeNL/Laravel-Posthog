<?php

namespace QodeNL\LaravelPosthog\Tests\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use QodeNL\LaravelPosthog\Jobs\PosthogCaptureJob;
use QodeNL\LaravelPosthog\Tests\TestCase;

class PosthogCaptureJobTest extends TestCase
{
    #[Test]
    public function it_implements_should_queue(): void
    {
        $job = new PosthogCaptureJob('session-123', 'test-event');

        $this->assertInstanceOf(ShouldQueue::class, $job);
    }

    #[Test]
    public function it_uses_default_queue(): void
    {
        config()->set('posthog.queue.queue', 'default');
        config()->set('posthog.queue.connection', null);

        $job = new PosthogCaptureJob('session-123', 'test-event');

        $this->assertEquals('default', $job->queue);
        $this->assertNull($job->connection);
    }

    #[Test]
    public function it_uses_custom_queue_and_connection(): void
    {
        config()->set('posthog.queue.queue', 'posthog');
        config()->set('posthog.queue.connection', 'redis');

        $job = new PosthogCaptureJob('session-123', 'test-event');

        $this->assertEquals('posthog', $job->queue);
        $this->assertEquals('redis', $job->connection);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function handle_calls_posthog_capture_with_correct_payload(): void
    {
        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->once();
        $mock->shouldReceive('capture')->once()->with(Mockery::on(function ($args) {
            return $args['distinctId'] === 'session-123'
                && $args['event'] === 'test-event'
                && $args['properties'] === ['key' => 'value']
                && $args['timestamp'] === null;
        }));

        $job = new PosthogCaptureJob('session-123', 'test-event', ['key' => 'value']);
        $job->handle();
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function handle_passes_timestamp_when_provided(): void
    {
        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->once();
        $mock->shouldReceive('capture')->once()->with(Mockery::on(function ($args) {
            return $args['timestamp'] === 1234567890;
        }));

        $job = new PosthogCaptureJob('session-123', 'test-event', [], 1234567890);
        $job->handle();
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function handle_includes_groups_in_payload_when_provided(): void
    {
        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->once();
        $mock->shouldReceive('capture')->once()->with(Mockery::on(function ($args) {
            return $args['distinctId'] === 'session-123'
                && $args['event'] === 'test-event'
                && $args['$groups'] === ['company' => 'id:5'];
        }));

        $job = new PosthogCaptureJob('session-123', 'test-event', [], null, ['company' => 'id:5']);
        $job->handle();
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function handle_omits_groups_when_empty(): void
    {
        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->once();
        $mock->shouldReceive('capture')->once()->with(Mockery::on(function ($args) {
            return ! array_key_exists('$groups', $args);
        }));

        $job = new PosthogCaptureJob('session-123', 'test-event');
        $job->handle();
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function handle_catches_exception_and_logs(): void
    {
        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->once();
        $mock->shouldReceive('capture')->once()->andThrow(new \Exception('API error'));

        Log::shouldReceive('info')->once()->with('Posthog capture call failed:API error');

        $job = new PosthogCaptureJob('session-123', 'test-event');
        $job->handle();
    }
}
