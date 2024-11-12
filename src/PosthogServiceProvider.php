<?php

namespace QodeNL\LaravelPosthog;

use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;

class PosthogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind('LaravelPosthog', function ($app) {
            return new LaravelPosthog();
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/posthog.php' => config_path('posthog.php'),
        ]);

        Feature::extend('posthog', fn () => new Extensions\PosthogFeatureDriver());
    }
}
