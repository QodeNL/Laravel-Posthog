<?php

namespace QodeNL\LaravelPosthog\Facades;

use Illuminate\Support\Facades\Facade;

class Posthog extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'LaravelPosthog';
    }
}
