<?php

namespace QodeNL\LaravelPosthog\Tests;

use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use PostHog\PostHog;
use QodeNL\LaravelPosthog\Extensions\PosthogFeatureDriver;
use QodeNL\LaravelPosthog\LaravelPosthog;
use QodeNL\LaravelPosthog\Tests\Stubs\TestUser;

class SdkIntegrationTest extends TestCase
{
    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function flag_checks_never_reach_a_deprecated_sdk_method(): void
    {
        $this->useUnreachablePosthog();

        Auth::shouldReceive('user')->andReturnNull();

        $user = new TestUser;
        $user->id = 1;

        $deprecations = $this->collectDeprecations(function () use ($user) {
            $posthog = new LaravelPosthog;
            $posthog->getFeatureFlag('some-flag');
            $posthog->isFeatureEnabled('some-flag');
            $posthog->getAllFlags();

            (new PosthogFeatureDriver)->getAll(['flag-a' => [$user], 'flag-b' => [$user]]);
        });

        $this->assertSame([], $deprecations);

        // Guard against a vacuous assertion: the legacy API this package moved off of must still
        // register as a deprecation through the same harness.
        $legacy = $this->collectDeprecations(fn () => PostHog::getFeatureFlag('some-flag', 'user:1'));

        $this->assertNotEmpty($legacy);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function an_unreachable_posthog_resolves_flags_to_false_without_throwing(): void
    {
        $this->useUnreachablePosthog();

        Auth::shouldReceive('user')->andReturnNull();

        $user = new TestUser;
        $user->id = 1;

        $posthog = new LaravelPosthog;

        $this->assertNull($posthog->getFeatureFlag('some-flag'));
        $this->assertFalse($posthog->isFeatureEnabled('some-flag'));
        $this->assertSame([], $posthog->getAllFlags());
        $this->assertSame([false], (new PosthogFeatureDriver)->get('some-flag', [$user]));
    }

    /**
     * Point the SDK at a closed port and keep its diagnostics out of the test output.
     */
    private function useUnreachablePosthog(): void
    {
        config()->set('posthog.host', 'http://127.0.0.1:9');
        config()->set('posthog.client.consumer', 'noop');
        config()->set('posthog.client.timeout', 200);
        config()->set('posthog.feature_flags.request_timeout_ms', 200);
        config()->set('posthog.feature_flags.request_max_retries', 0);

        ini_set('error_log', '/dev/null');
    }

    /**
     * @return list<string> the E_USER_DEPRECATED messages raised while running $callback
     */
    private function collectDeprecations(callable $callback): array
    {
        $deprecations = [];

        set_error_handler(function (int $errno, string $message) use (&$deprecations) {
            $deprecations[] = $message;

            return true;
        }, E_USER_DEPRECATED);

        try {
            $callback();
        } finally {
            restore_error_handler();
        }

        return $deprecations;
    }
}
