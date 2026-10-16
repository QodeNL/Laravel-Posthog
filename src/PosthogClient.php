<?php

namespace QodeNL\LaravelPosthog;

final class PosthogClient
{
    public static bool $initialized = false;

    public static function reset(): void
    {
        self::$initialized = false;
    }
}
