<?php

namespace QodeNL\LaravelPosthog\Tests\Facades;

use PHPUnit\Framework\Attributes\Test;
use QodeNL\LaravelPosthog\Facades\Posthog;
use QodeNL\LaravelPosthog\LaravelPosthog;
use QodeNL\LaravelPosthog\Tests\TestCase;

class PosthogFacadeTest extends TestCase
{
    #[Test]
    public function facade_accessor_returns_correct_string(): void
    {
        $reflection = new \ReflectionMethod(Posthog::class, 'getFacadeAccessor');

        $this->assertSame('LaravelPosthog', $reflection->invoke(null));
    }

    #[Test]
    public function facade_resolves_to_laravel_posthog_instance(): void
    {
        $instance = Posthog::getFacadeRoot();

        $this->assertInstanceOf(LaravelPosthog::class, $instance);
    }

    #[Test]
    public function resolve_distinct_id_using_proxies_to_laravel_posthog(): void
    {
        Posthog::resolveDistinctIdUsing(fn () => 'custom-facade-id');

        $instance = new LaravelPosthog;

        $reflection = new \ReflectionProperty($instance, 'sessionId');
        $this->assertSame('custom-facade-id', $reflection->getValue($instance));
    }
}
