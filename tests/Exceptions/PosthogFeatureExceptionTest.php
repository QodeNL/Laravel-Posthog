<?php

namespace QodeNL\LaravelPosthog\Tests\Exceptions;

use Exception;
use PHPUnit\Framework\Attributes\Test;
use QodeNL\LaravelPosthog\Exceptions\PosthogFeatureException;
use QodeNL\LaravelPosthog\Tests\TestCase;

class PosthogFeatureExceptionTest extends TestCase
{
    #[Test]
    public function setting_not_supported_returns_exception_with_correct_message(): void
    {
        $exception = PosthogFeatureException::settingNotSupported();

        $this->assertInstanceOf(PosthogFeatureException::class, $exception);
        $this->assertInstanceOf(Exception::class, $exception);
        $this->assertSame('Posthog does not support setting feature flags', $exception->getMessage());
    }

    #[Test]
    public function deleting_not_supported_returns_exception_with_correct_message(): void
    {
        $exception = PosthogFeatureException::deletingNotSupported();

        $this->assertInstanceOf(PosthogFeatureException::class, $exception);
        $this->assertSame('Posthog does not support deleting feature flags', $exception->getMessage());
    }

    #[Test]
    public function purging_not_supported_returns_exception_with_correct_message(): void
    {
        $exception = PosthogFeatureException::purgingNotSupported();

        $this->assertInstanceOf(PosthogFeatureException::class, $exception);
        $this->assertSame('Posthog does not support purging feature flags', $exception->getMessage());
    }
}
