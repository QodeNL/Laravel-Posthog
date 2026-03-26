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
