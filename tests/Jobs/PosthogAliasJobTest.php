<?php

namespace QodeNL\LaravelPosthog\Tests\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use QodeNL\LaravelPosthog\Jobs\PosthogAliasJob;
use QodeNL\LaravelPosthog\Tests\TestCase;

class PosthogAliasJobTest extends TestCase
{
    #[Test]
    public function it_implements_should_queue(): void
    {
        $job = new PosthogAliasJob('session-123', 'user-456');

        $this->assertInstanceOf(ShouldQueue::class, $job);
    }

    #[Test]
    public function it_uses_default_queue(): void
    {
        config()->set('posthog.queue.queue', 'default');
        config()->set('posthog.queue.connection', null);

        $job = new PosthogAliasJob('session-123', 'user-456');

        $this->assertEquals('default', $job->queue);
        $this->assertNull($job->connection);
    }

    #[Test]
    public function it_uses_custom_queue_and_connection(): void
    {
        config()->set('posthog.queue.queue', 'posthog');
        config()->set('posthog.queue.connection', 'redis');

        $job = new PosthogAliasJob('session-123', 'user-456');

        $this->assertEquals('posthog', $job->queue);
        $this->assertEquals('redis', $job->connection);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function handle_calls_posthog_alias_with_correct_payload(): void
    {
        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->once();
        $mock->shouldReceive('alias')->once()->with(Mockery::on(function ($args) {
            return $args['distinctId'] === 'user-456'
                && $args['alias'] === 'session-123'
                && $args['timestamp'] === null;
        }));

        $job = new PosthogAliasJob('session-123', 'user-456');
        $job->handle();
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function handle_catches_exception_and_logs(): void
    {
        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->once();
        $mock->shouldReceive('alias')->once()->andThrow(new \Exception('API error'));

        Log::shouldReceive('info')->once()->with('Posthog alias call failed:API error');

        $job = new PosthogAliasJob('session-123', 'user-456');
        $job->handle();
    }
}
