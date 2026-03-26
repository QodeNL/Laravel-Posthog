<?php

namespace QodeNL\LaravelPosthog\Tests\Extensions;

use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use QodeNL\LaravelPosthog\Exceptions\PosthogFeatureException;
use QodeNL\LaravelPosthog\Extensions\PosthogFeatureDriver;
use QodeNL\LaravelPosthog\Tests\Stubs\TestScopeWithPosthogIdentifier;
use QodeNL\LaravelPosthog\Tests\Stubs\TestUser;
use QodeNL\LaravelPosthog\Tests\TestCase;

class PosthogFeatureDriverTest extends TestCase
{
    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function get_resolves_scope_with_posthog_identifier(): void
    {
        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->once();
        $mock->shouldReceive('getFeatureFlag')
            ->once()
            ->with('test-flag', 'custom-scope-id', Mockery::any(), Mockery::any())
            ->andReturn(true);

        $driver = new PosthogFeatureDriver;
        $scope = new TestScopeWithPosthogIdentifier;

        $result = $driver->get('test-flag', [$scope]);

        $this->assertSame([true], $result);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function get_resolves_authenticatable_scope_with_prefix(): void
    {
        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->once();
        $mock->shouldReceive('getFeatureFlag')
            ->once()
            ->with('test-flag', 'user:99', Mockery::any(), Mockery::any())
            ->andReturn('variant-a');

        config()->set('posthog.user_prefix', 'user');

        $user = new TestUser;
        $user->id = 99;

        $driver = new PosthogFeatureDriver;
        $result = $driver->get('test-flag', [$user]);

        $this->assertSame(['variant-a'], $result);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function get_returns_false_for_null_scope(): void
    {
        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->once();
        $mock->shouldNotReceive('getFeatureFlag');

        $driver = new PosthogFeatureDriver;
        $result = $driver->get('test-flag', [null]);

        $this->assertSame([false], $result);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function get_returns_default_enabled_on_sdk_exception(): void
    {
        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->once();
        $mock->shouldReceive('getFeatureFlag')->once()->andThrow(new \Exception('SDK error'));

        config()->set('posthog.feature_flags.default_enabled', true);

        $user = new TestUser;
        $user->id = 1;

        $driver = new PosthogFeatureDriver;
        $result = $driver->get('test-flag', [$user]);

        $this->assertSame([true], $result);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function get_all_maps_over_get(): void
    {
        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->once();
        $mock->shouldReceive('getFeatureFlag')->twice()->andReturn(true, false);

        $user = new TestUser;
        $user->id = 1;

        $driver = new PosthogFeatureDriver;
        $result = $driver->getAll([
            'flag-a' => [$user],
            'flag-b' => [$user],
        ]);

        $this->assertArrayHasKey('flag-a', $result);
        $this->assertArrayHasKey('flag-b', $result);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function define_is_noop(): void
    {
        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->once();

        $driver = new PosthogFeatureDriver;
        $driver->define('test', fn () => true);

        $this->assertTrue(true);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function defined_returns_empty_array(): void
    {
        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->once();

        $driver = new PosthogFeatureDriver;

        $this->assertSame([], $driver->defined());
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function set_throws_posthog_feature_exception(): void
    {
        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->once();

        $this->expectException(PosthogFeatureException::class);

        $driver = new PosthogFeatureDriver;
        $driver->set('test-flag', null, true);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function set_for_all_scopes_throws_posthog_feature_exception(): void
    {
        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->once();

        $this->expectException(PosthogFeatureException::class);

        $driver = new PosthogFeatureDriver;
        $driver->setForAllScopes('test-flag', true);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function delete_throws_posthog_feature_exception(): void
    {
        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->once();

        $this->expectException(PosthogFeatureException::class);

        $driver = new PosthogFeatureDriver;
        $driver->delete('test-flag', null);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function purge_throws_posthog_feature_exception(): void
    {
        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->once();

        $this->expectException(PosthogFeatureException::class);

        $driver = new PosthogFeatureDriver;
        $driver->purge(null);
    }
}
