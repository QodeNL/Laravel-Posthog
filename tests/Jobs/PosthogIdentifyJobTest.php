<?php

namespace QodeNL\LaravelPosthog\Tests\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use QodeNL\LaravelPosthog\Jobs\PosthogIdentifyJob;
use QodeNL\LaravelPosthog\Tests\TestCase;

class PosthogIdentifyJobTest extends TestCase
{
    #[Test]
    public function it_implements_should_queue(): void
    {
        $job = new PosthogIdentifyJob('session-123');

        $this->assertInstanceOf(ShouldQueue::class, $job);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function handle_merges_email_into_properties(): void
    {
        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->once();
        $mock->shouldReceive('identify')->once()->with(Mockery::on(function ($args) {
            return $args['distinctId'] === 'session-123'
                && $args['properties'] === ['email' => 'test@example.com', 'name' => 'John']
                && $args['timestamp'] === null;
        }));

        $job = new PosthogIdentifyJob('session-123', 'test@example.com', ['name' => 'John']);
        $job->handle();
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function handle_omits_email_when_empty(): void
    {
        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->once();
        $mock->shouldReceive('identify')->once()->with(Mockery::on(function ($args) {
            return $args['properties'] === ['name' => 'John']
                && ! array_key_exists('email', $args['properties']);
        }));

        $job = new PosthogIdentifyJob('session-123', '', ['name' => 'John']);
        $job->handle();
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function handle_catches_exception_and_logs(): void
    {
        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->once();
        $mock->shouldReceive('identify')->once()->andThrow(new \Exception('API error'));

        Log::shouldReceive('info')->once()->with('Posthog identify call failed:API error');

        $job = new PosthogIdentifyJob('session-123', 'test@example.com');
        $job->handle();
    }
}
