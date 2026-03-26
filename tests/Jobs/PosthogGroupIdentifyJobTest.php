<?php

namespace QodeNL\LaravelPosthog\Tests\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use QodeNL\LaravelPosthog\Jobs\PosthogGroupIdentifyJob;
use QodeNL\LaravelPosthog\Tests\TestCase;

class PosthogGroupIdentifyJobTest extends TestCase
{
    #[Test]
    public function it_implements_should_queue(): void
    {
        $job = new PosthogGroupIdentifyJob('company', 'id:5');

        $this->assertInstanceOf(ShouldQueue::class, $job);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function handle_calls_posthog_group_identify_with_correct_payload(): void
    {
        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->once();
        $mock->shouldReceive('groupIdentify')->once()->with(Mockery::on(function ($args) {
            return $args['groupType'] === 'company'
                && $args['groupKey'] === 'id:5'
                && $args['properties'] === ['name' => 'Acme Inc'];
        }));

        $job = new PosthogGroupIdentifyJob('company', 'id:5', ['name' => 'Acme Inc']);
        $job->handle();
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function handle_catches_exception_and_logs(): void
    {
        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->once();
        $mock->shouldReceive('groupIdentify')->once()->andThrow(new \Exception('API error'));

        Log::shouldReceive('info')->once()->with('Posthog groupIdentify call failed:API error');

        $job = new PosthogGroupIdentifyJob('company', 'id:5');
        $job->handle();
    }
}
