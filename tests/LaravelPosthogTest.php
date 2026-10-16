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
use RuntimeException;

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
        Auth::shouldReceive('id')->andReturn(42);
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
        Auth::shouldReceive('id')->andReturn(42);
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
    public function set_group_with_properties_dispatches_group_identify_job(): void
    {
        Bus::fake();
        Auth::shouldReceive('user')->andReturnNull();

        $instance = new LaravelPosthog;
        $result = $instance->setGroup('company', 'id:5', ['name' => 'Acme Inc']);

        $this->assertSame($instance, $result);
        $this->assertSame(['company' => 'id:5'], $instance->getGroups());

        Bus::assertDispatched(PosthogGroupIdentifyJob::class, function ($job) {
            $type = (new \ReflectionProperty($job, 'groupType'))->getValue($job);
            $key = (new \ReflectionProperty($job, 'groupKey'))->getValue($job);
            $props = (new \ReflectionProperty($job, 'properties'))->getValue($job);

            return $type === 'company' && $key === 'id:5' && $props === ['name' => 'Acme Inc'];
        });
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
    public function capture_does_not_add_ip_by_default(): void
    {
        Bus::fake();
        Auth::shouldReceive('user')->andReturnNull();
        $this->simulateHttpRequest('203.0.113.7');

        (new LaravelPosthog)->capture('test-event', ['key' => 'value']);

        Bus::assertDispatched(PosthogCaptureJob::class, function ($job) {
            return (new \ReflectionProperty($job, 'properties'))->getValue($job) === ['key' => 'value'];
        });
    }

    #[Test]
    public function capture_and_identify_add_request_ip_when_enabled(): void
    {
        Bus::fake();
        Auth::shouldReceive('user')->andReturnNull();
        config()->set('posthog.capture_ip', true);
        $this->simulateHttpRequest('203.0.113.7');

        $instance = new LaravelPosthog;
        $instance->capture('test-event', ['key' => 'value']);
        $instance->identify('test@example.com');

        Bus::assertDispatched(PosthogCaptureJob::class, function ($job) {
            return (new \ReflectionProperty($job, 'properties'))->getValue($job) === ['key' => 'value', '$ip' => '203.0.113.7'];
        });
        Bus::assertDispatched(PosthogIdentifyJob::class, function ($job) {
            return (new \ReflectionProperty($job, 'properties'))->getValue($job) === ['$ip' => '203.0.113.7'];
        });
    }

    #[Test]
    public function capture_keeps_explicit_ip(): void
    {
        Bus::fake();
        Auth::shouldReceive('user')->andReturnNull();
        config()->set('posthog.capture_ip', true);
        $this->simulateHttpRequest('203.0.113.7');

        (new LaravelPosthog)->capture('test-event', ['$ip' => '198.51.100.1']);

        Bus::assertDispatched(PosthogCaptureJob::class, function ($job) {
            return (new \ReflectionProperty($job, 'properties'))->getValue($job) === ['$ip' => '198.51.100.1'];
        });
    }

    #[Test]
    public function capture_does_not_add_ip_in_console(): void
    {
        Bus::fake();
        Auth::shouldReceive('user')->andReturnNull();
        config()->set('posthog.capture_ip', true);

        (new LaravelPosthog)->capture('test-event');

        Bus::assertDispatched(PosthogCaptureJob::class, function ($job) {
            return (new \ReflectionProperty($job, 'properties'))->getValue($job) === [];
        });
    }

    #[Test]
    public function capture_uses_custom_ip_resolver(): void
    {
        Bus::fake();
        Auth::shouldReceive('user')->andReturnNull();
        config()->set('posthog.capture_ip', true);
        $this->simulateHttpRequest('203.0.113.7');

        LaravelPosthog::resolveIpUsing(fn () => '198.51.100.2');

        (new LaravelPosthog)->capture('test-event');

        Bus::assertDispatched(PosthogCaptureJob::class, function ($job) {
            return (new \ReflectionProperty($job, 'properties'))->getValue($job) === ['$ip' => '198.51.100.2'];
        });
    }

    #[Test]
    public function custom_ip_resolver_is_ignored_when_capture_ip_is_disabled(): void
    {
        Bus::fake();
        Auth::shouldReceive('user')->andReturnNull();

        LaravelPosthog::resolveIpUsing(fn () => '198.51.100.2');

        (new LaravelPosthog)->capture('test-event');

        Bus::assertDispatched(PosthogCaptureJob::class, function ($job) {
            return (new \ReflectionProperty($job, 'properties'))->getValue($job) === [];
        });
    }

    private function simulateHttpRequest(string $ip): void
    {
        (new \ReflectionProperty($this->app, 'isRunningInConsole'))->setValue($this->app, false);
        $this->app['request']->server->set('REMOTE_ADDR', $ip);
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
    public function capture_exception_sends_with_distinct_id_groups_and_properties(): void
    {
        $exception = new RuntimeException('boom');

        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->once();
        $mock->shouldReceive('captureException')
            ->once()
            ->with($exception, 'user:42', ['source' => 'checkout', '$groups' => ['company' => 'company_id_5']])
            ->andReturnTrue();

        $user = new TestUser;
        $user->id = 42;
        Auth::shouldReceive('user')->andReturn($user);
        Auth::shouldReceive('id')->andReturn(42);

        $instance = new LaravelPosthog;
        $instance->setGroup('company', 'company_id_5');
        $instance->captureException($exception, ['source' => 'checkout']);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function capture_exception_logs_and_swallows_sdk_errors(): void
    {
        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->once();
        $mock->shouldReceive('captureException')->once()->andThrow(new \Exception('SDK down'));

        Auth::shouldReceive('user')->andReturnNull();
        Log::shouldReceive('info')->once()->with('Posthog captureException call failed: SDK down');

        (new LaravelPosthog)->captureException(new RuntimeException('boom'));
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function capture_exception_adds_ip_when_enabled(): void
    {
        $exception = new RuntimeException('boom');

        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->once();
        $mock->shouldReceive('captureException')
            ->once()
            ->with($exception, Mockery::any(), ['source' => 'checkout', '$ip' => '203.0.113.7'])
            ->andReturnTrue();

        Auth::shouldReceive('user')->andReturnNull();
        config()->set('posthog.capture_ip', true);
        $this->simulateHttpRequest('203.0.113.7');

        (new LaravelPosthog)->captureException($exception, ['source' => 'checkout']);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function capture_exception_does_nothing_when_disabled(): void
    {
        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldNotReceive('init');
        $mock->shouldNotReceive('captureException');

        Auth::shouldReceive('user')->andReturnNull();
        config()->set('posthog.enabled', false);

        (new LaravelPosthog)->captureException(new RuntimeException('boom'));
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function get_feature_flag_calls_sdk_when_enabled(): void
    {
        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->atLeast()->once();
        $mock->shouldReceive('evaluateFlags')
            ->once()
            ->andReturn($this->fakeFlagSnapshot(['test-flag' => 'variant-a']));

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
        $mock->shouldReceive('init')->atLeast()->once();
        $mock->shouldReceive('evaluateFlags')
            ->once()
            ->andReturn($this->fakeFlagSnapshot(['test-flag' => 'variant-a']));

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
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function repeated_flag_checks_share_a_single_flags_request(): void
    {
        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->atLeast()->once();
        $mock->shouldReceive('evaluateFlags')
            ->once()
            ->andReturn($this->fakeFlagSnapshot(['flag-a' => true, 'flag-b' => 'variant-b']));

        Auth::shouldReceive('user')->andReturnNull();

        $instance = new LaravelPosthog;

        $this->assertTrue($instance->getFeatureFlag('flag-a'));
        $this->assertSame('variant-b', $instance->getFeatureFlag('flag-b'));
        $this->assertTrue($instance->isFeatureEnabled('flag-a'));
        $this->assertNull($instance->getFeatureFlag('unknown-flag'));
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function get_feature_flag_payload_reads_from_the_shared_snapshot(): void
    {
        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->atLeast()->once();
        $mock->shouldReceive('evaluateFlags')
            ->once()
            ->andReturn($this->fakeFlagSnapshot(
                ['test-flag' => 'variant-a'],
                ['test-flag' => ['color' => 'blue']],
            ));

        Auth::shouldReceive('user')->andReturnNull();

        $instance = new LaravelPosthog;

        $this->assertSame('variant-a', $instance->getFeatureFlag('test-flag'));
        $this->assertSame(['color' => 'blue'], $instance->getFeatureFlagPayload('test-flag'));
        $this->assertNull($instance->getFeatureFlagPayload('unknown-flag'));
    }

    #[Test]
    public function get_feature_flag_payload_returns_null_when_disabled(): void
    {
        Auth::shouldReceive('user')->andReturnNull();

        config()->set('posthog.enabled', false);

        $instance = new LaravelPosthog;

        $this->assertNull($instance->getFeatureFlagPayload('test-flag'));
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
