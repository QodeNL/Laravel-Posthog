<?php

namespace QodeNL\LaravelPosthog\Tests;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use QodeNL\LaravelPosthog\Jobs\PosthogAliasJob;
use QodeNL\LaravelPosthog\Jobs\PosthogCaptureJob;
use QodeNL\LaravelPosthog\Jobs\PosthogGroupIdentifyJob;
use QodeNL\LaravelPosthog\Jobs\PosthogIdentifyJob;
use QodeNL\LaravelPosthog\LaravelPosthog;
use QodeNL\LaravelPosthog\Tests\Stubs\TestUser;

class LaravelPosthogTest extends TestCase
{
    #[Test]
    public function it_uses_session_hash_for_unauthenticated_user(): void
    {
        Auth::shouldReceive('user')->andReturnNull();
        session()->put('_token', 'test');

        $instance = new LaravelPosthog;

        $reflection = new \ReflectionProperty($instance, 'sessionId');
        $sessionId = $reflection->getValue($instance);

        $this->assertSame(sha1(session()->getId()), $sessionId);
    }

    #[Test]
    public function it_uses_prefixed_user_id_for_authenticated_user(): void
    {
        $user = new TestUser;
        $user->id = 42;

        Auth::shouldReceive('user')->andReturn($user);
        config()->set('posthog.user_prefix', 'user');

        $instance = new LaravelPosthog;

        $reflection = new \ReflectionProperty($instance, 'sessionId');
        $this->assertSame('user:42', $reflection->getValue($instance));
    }

    #[Test]
    public function it_uses_raw_id_when_prefix_is_empty(): void
    {
        $user = new TestUser;
        $user->id = 42;

        Auth::shouldReceive('user')->andReturn($user);
        config()->set('posthog.user_prefix', '');

        $instance = new LaravelPosthog;

        $reflection = new \ReflectionProperty($instance, 'sessionId');
        $this->assertSame('42', (string) $reflection->getValue($instance));
    }

    #[Test]
    public function it_uses_custom_distinct_id_resolver(): void
    {
        LaravelPosthog::resolveDistinctIdUsing(fn () => 'custom-id-123');

        $instance = new LaravelPosthog;

        $reflection = new \ReflectionProperty($instance, 'sessionId');
        $this->assertSame('custom-id-123', $reflection->getValue($instance));
    }

    #[Test]
    public function null_resolver_falls_back_to_default(): void
    {
        LaravelPosthog::resolveDistinctIdUsing(fn () => 'temp');
        LaravelPosthog::resolveDistinctIdUsing(null);

        Auth::shouldReceive('user')->andReturnNull();

        $instance = new LaravelPosthog;

        $reflection = new \ReflectionProperty($instance, 'sessionId');
        $sessionId = $reflection->getValue($instance);

        $this->assertSame(sha1(session()->getId()), $sessionId);
    }

    #[Test]
    public function set_group_stores_group_and_returns_self(): void
    {
        Auth::shouldReceive('user')->andReturnNull();

        $instance = new LaravelPosthog;
        $result = $instance->setGroup('company', 'id:5');

        $this->assertSame($instance, $result);
        $this->assertSame(['company' => 'id:5'], $instance->getGroups());
    }

    #[Test]
    public function set_group_supports_multiple_groups(): void
    {
        Auth::shouldReceive('user')->andReturnNull();

        $instance = new LaravelPosthog;
        $instance->setGroup('company', 'id:5')->setGroup('project', 'id:10');

        $this->assertSame(['company' => 'id:5', 'project' => 'id:10'], $instance->getGroups());
    }

    #[Test]
    public function capture_dispatches_job_with_groups(): void
    {
        Bus::fake();
        Auth::shouldReceive('user')->andReturnNull();

        $instance = new LaravelPosthog;
        $instance->setGroup('company', 'id:5');
        $instance->capture('test-event', ['key' => 'value']);

        Bus::assertDispatched(PosthogCaptureJob::class, function ($job) {
            $reflection = new \ReflectionProperty($job, 'groups');

            return $reflection->getValue($job) === ['company' => 'id:5'];
        });
    }

    #[Test]
    public function group_identify_dispatches_job_when_enabled(): void
    {
        Bus::fake();
        Auth::shouldReceive('user')->andReturnNull();

        $instance = new LaravelPosthog;
        $instance->updateOrCreateGroup('company', 'id:5', ['name' => 'Acme Inc']);

        Bus::assertDispatched(PosthogGroupIdentifyJob::class, function ($job) {
            $type = (new \ReflectionProperty($job, 'groupType'))->getValue($job);
            $key = (new \ReflectionProperty($job, 'groupKey'))->getValue($job);
            $props = (new \ReflectionProperty($job, 'properties'))->getValue($job);

            return $type === 'company' && $key === 'id:5' && $props === ['name' => 'Acme Inc'];
        });
    }

    #[Test]
    public function group_identify_does_not_dispatch_when_disabled(): void
    {
        Bus::fake();
        Log::shouldReceive('debug')->once()->with('PosthogGroupIdentifyJob not dispatched because posthog is disabled');
        Auth::shouldReceive('user')->andReturnNull();

        config()->set('posthog.enabled', false);

        $instance = new LaravelPosthog;
        $instance->updateOrCreateGroup('company', 'id:5', ['name' => 'Acme Inc']);

        Bus::assertNotDispatched(PosthogGroupIdentifyJob::class);
    }

    #[Test]
    public function capture_dispatches_job_when_enabled(): void
    {
        Bus::fake();
        Auth::shouldReceive('user')->andReturnNull();

        $instance = new LaravelPosthog;
        $instance->capture('test-event', ['key' => 'value']);

        Bus::assertDispatched(PosthogCaptureJob::class, function ($job) {
            $reflection = new \ReflectionProperty($job, 'event');

            return $reflection->getValue($job) === 'test-event';
        });
    }

    #[Test]
    public function capture_does_not_dispatch_when_disabled(): void
    {
        Bus::fake();
        Log::shouldReceive('debug')->once()->with('PosthogCaptureJob not dispatched because posthog is disabled');
        Auth::shouldReceive('user')->andReturnNull();

        config()->set('posthog.enabled', false);

        $instance = new LaravelPosthog;
        $instance->capture('test-event');

        Bus::assertNotDispatched(PosthogCaptureJob::class);
    }

    #[Test]
    public function capture_does_not_dispatch_when_key_is_empty(): void
    {
        Bus::fake();
        Log::shouldReceive('debug')->once();
        Auth::shouldReceive('user')->andReturnNull();

        config()->set('posthog.key', '');

        $instance = new LaravelPosthog;
        $instance->capture('test-event');

        Bus::assertNotDispatched(PosthogCaptureJob::class);
    }

    #[Test]
    public function identify_dispatches_job_when_enabled(): void
    {
        Bus::fake();
        Auth::shouldReceive('user')->andReturnNull();

        $instance = new LaravelPosthog;
        $instance->identify('test@example.com', ['name' => 'Test']);

        Bus::assertDispatched(PosthogIdentifyJob::class, function ($job) {
            $reflection = new \ReflectionProperty($job, 'email');

            return $reflection->getValue($job) === 'test@example.com';
        });
    }

    #[Test]
    public function identify_does_not_dispatch_when_disabled(): void
    {
        Bus::fake();
        Log::shouldReceive('debug')->once()->with('PosthogIdentifyJob not dispatched because posthog is disabled');
        Auth::shouldReceive('user')->andReturnNull();

        config()->set('posthog.enabled', false);

        $instance = new LaravelPosthog;
        $instance->identify('test@example.com');

        Bus::assertNotDispatched(PosthogIdentifyJob::class);
    }

    #[Test]
    public function alias_dispatches_job_when_enabled(): void
    {
        Bus::fake();
        Auth::shouldReceive('user')->andReturnNull();

        $instance = new LaravelPosthog;
        $instance->alias('user-456');

        Bus::assertDispatched(PosthogAliasJob::class, function ($job) {
            $reflection = new \ReflectionProperty($job, 'userId');

            return $reflection->getValue($job) === 'user-456';
        });
    }

    #[Test]
    public function alias_does_not_dispatch_when_disabled(): void
    {
        Bus::fake();
        Log::shouldReceive('debug')->once()->with('PosthogAliasJob not dispatched because posthog is disabled');
        Auth::shouldReceive('user')->andReturnNull();

        config()->set('posthog.enabled', false);

        $instance = new LaravelPosthog;
        $instance->alias('user-456');

        Bus::assertNotDispatched(PosthogAliasJob::class);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function get_feature_flag_calls_sdk_when_enabled(): void
    {
        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->once();
        $mock->shouldReceive('getFeatureFlag')->once()->andReturn('variant-a');

        Auth::shouldReceive('user')->andReturnNull();

        $instance = new LaravelPosthog;
        $result = $instance->getFeatureFlag('test-flag');

        $this->assertSame('variant-a', $result);
    }

    #[Test]
    public function get_feature_flag_returns_default_when_disabled(): void
    {
        Auth::shouldReceive('user')->andReturnNull();

        config()->set('posthog.enabled', false);
        config()->set('posthog.feature_flags.default_enabled', true);

        $instance = new LaravelPosthog;
        $result = $instance->getFeatureFlag('test-flag');

        $this->assertTrue($result);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function is_feature_enabled_returns_bool(): void
    {
        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->once();
        $mock->shouldReceive('getFeatureFlag')->once()->andReturn('variant-a');

        Auth::shouldReceive('user')->andReturnNull();

        $instance = new LaravelPosthog;
        $result = $instance->isFeatureEnabled('test-flag');

        $this->assertTrue($result);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function get_all_flags_calls_sdk_when_enabled(): void
    {
        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->once();
        $mock->shouldReceive('getAllFlags')->once()->andReturn(['flag1' => true, 'flag2' => 'variant-b']);

        Auth::shouldReceive('user')->andReturnNull();

        $instance = new LaravelPosthog;
        $result = $instance->getAllFlags();

        $this->assertSame(['flag1' => true, 'flag2' => 'variant-b'], $result);
    }

    #[Test]
    public function get_all_flags_returns_empty_array_when_disabled(): void
    {
        Auth::shouldReceive('user')->andReturnNull();

        config()->set('posthog.enabled', false);

        $instance = new LaravelPosthog;
        $result = $instance->getAllFlags();

        $this->assertSame([], $result);
    }
}
