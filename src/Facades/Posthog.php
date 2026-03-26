<?php

namespace QodeNL\LaravelPosthog\Facades;

use Closure;
use Illuminate\Support\Facades\Facade;
use QodeNL\LaravelPosthog\LaravelPosthog;

class Posthog extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'LaravelPosthog';
    }

    public static function resolveDistinctIdUsing(?Closure $callback): void
    {
        LaravelPosthog::resolveDistinctIdUsing($callback);
    }
}
