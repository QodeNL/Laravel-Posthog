<?php

namespace QodeNL\LaravelPosthog;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;

class PosthogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped('LaravelPosthog', function ($app) {
            return new LaravelPosthog;
        });

        $this->app->scoped(PosthogFlagSnapshots::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/posthog.php' => config_path('posthog.php'),
        ]);

        Feature::extend('posthog', fn () => new Extensions\PosthogFeatureDriver);

        $this->registerExceptionReporting();
    }

    private function registerExceptionReporting(): void
    {
        if (! config('posthog.error_tracking.enabled')) {
            return;
        }

        $this->callAfterResolving(ExceptionHandler::class, function ($handler) {
            if (method_exists($handler, 'reportable')) {
                $handler->reportable(new PosthogExceptionReporter);
            }
        });
    }
}
