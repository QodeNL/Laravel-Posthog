<?php

namespace QodeNL\LaravelPosthog\Tests;

use Illuminate\Support\ServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use QodeNL\LaravelPosthog\LaravelPosthog;
use QodeNL\LaravelPosthog\PosthogServiceProvider;

class PosthogServiceProviderTest extends TestCase
{
    #[Test]
    public function it_registers_laravel_posthog_binding(): void
    {
        $resolved = $this->app->make('LaravelPosthog');

        $this->assertInstanceOf(LaravelPosthog::class, $resolved);
    }

    #[Test]
    public function binding_returns_same_instance_each_time(): void
    {
        $first = $this->app->make('LaravelPosthog');
        $second = $this->app->make('LaravelPosthog');

        $this->assertSame($first, $second);
    }

    #[Test]
    public function it_publishes_config_file(): void
    {
        $publishes = ServiceProvider::$publishes;

        $providerPublishes = $publishes[PosthogServiceProvider::class] ?? [];

        $this->assertNotEmpty($providerPublishes);

        $configSource = array_key_first($providerPublishes);
        $this->assertStringContainsString('config/posthog.php', $configSource);
    }
}
