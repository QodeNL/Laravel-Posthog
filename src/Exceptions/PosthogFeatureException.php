<?php

namespace QodeNL\LaravelPosthog\Exceptions;

use Exception;

class PosthogFeatureException extends Exception
{
    public static function settingNotSupported(): self
    {
        return new self('Posthog does not support setting feature flags');
    }

    public static function deletingNotSupported(): self
    {
        return new self('Posthog does not support deleting feature flags');
    }

    public static function purgingNotSupported(): self
    {
        return new self('Posthog does not support purging feature flags');
    }
}
