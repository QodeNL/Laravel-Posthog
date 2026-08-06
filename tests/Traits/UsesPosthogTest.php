<?php

namespace QodeNL\LaravelPosthog\Tests\Traits;

use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use QodeNL\LaravelPosthog\Tests\TestCase;
use QodeNL\LaravelPosthog\Traits\UsesPosthog;

class UsesPosthogTest extends TestCase
{
    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function posthog_init_calls_sdk_with_config(): void
    {
        config()->set('posthog.key', 'test-api-key');
        config()->set('posthog.host', 'https://test.posthog.com');

        $captured = $this->captureInitArguments();

        $this->instanceUsingPosthog()->posthogInit();

        $this->assertSame('test-api-key', $captured['apiKey']);
        $this->assertSame('https://test.posthog.com', $captured['options']['host']);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function posthog_init_passes_secret_key_for_local_evaluation(): void
    {
        config()->set('posthog.secret_key', 'phx_local_eval');

        $captured = $this->captureInitArguments();

        $this->instanceUsingPosthog()->posthogInit();

        $this->assertSame('phx_local_eval', $captured['secretKey']);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function posthog_init_forwards_configured_client_options(): void
    {
        config()->set('posthog.client.timeout', '2500');
        config()->set('posthog.client.batch_size', '25');
        config()->set('posthog.client.flush_interval_seconds', '1.5');
        config()->set('posthog.client.compress_request', true);
        config()->set('posthog.feature_flags.request_timeout_ms', '750');
        config()->set('posthog.feature_flags.request_max_retries', '0');

        $captured = $this->captureInitArguments();

        $this->instanceUsingPosthog()->posthogInit();

        $this->assertSame(2500, $captured['options']['timeout']);
        $this->assertSame(25, $captured['options']['batch_size']);
        $this->assertSame(1.5, $captured['options']['flush_interval_seconds']);
        $this->assertTrue($captured['options']['compress_request']);
        $this->assertSame(750, $captured['options']['feature_flag_request_timeout_ms']);
        $this->assertSame(0, $captured['options']['feature_flag_request_max_retries']);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function posthog_init_omits_unset_options_so_the_sdk_keeps_its_defaults(): void
    {
        // Mirrors a config file published before these keys existed: every one resolves to null.
        config()->set('posthog.client', []);
        config()->set('posthog.feature_flags.request_timeout_ms', null);
        config()->set('posthog.feature_flags.request_max_retries', null);

        $captured = $this->captureInitArguments();

        $this->instanceUsingPosthog()->posthogInit();

        $this->assertSame(['host'], array_keys($captured['options']));
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function posthog_options_are_overridable_through_the_raw_options_array(): void
    {
        config()->set('posthog.client.batch_size', 25);
        config()->set('posthog.options', ['batch_size' => 99, 'verify_batch_events_request' => false]);

        $captured = $this->captureInitArguments();

        $this->instanceUsingPosthog()->posthogInit();

        $this->assertSame(99, $captured['options']['batch_size']);
        $this->assertFalse($captured['options']['verify_batch_events_request']);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function send_events_disabled_installs_a_before_send_hook_that_drops_flag_called_events(): void
    {
        config()->set('posthog.feature_flags.send_events', false);

        $captured = $this->captureInitArguments();

        $this->instanceUsingPosthog()->posthogInit();

        $beforeSend = $captured['options']['before_send'];

        $this->assertNull($beforeSend(['event' => '$feature_flag_called']));
        $this->assertSame(['event' => 'ordinary'], $beforeSend(['event' => 'ordinary']));
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function send_events_enabled_installs_no_before_send_hook(): void
    {
        config()->set('posthog.feature_flags.send_events', true);

        $captured = $this->captureInitArguments();

        $this->instanceUsingPosthog()->posthogInit();

        $this->assertArrayNotHasKey('before_send', $captured['options']);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function posthog_init_catches_exception_and_logs_error(): void
    {
        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->once()->andThrow(new \Exception('Init failed'));

        Log::shouldReceive('error')->once()->with('Posthog initialization failed: Init failed');

        $instance = new class
        {
            use UsesPosthog;
        };

        $instance->posthogInit();
    }

    /**
     * Alias-mock PostHog::init() and hand back a holder for the arguments it receives.
     */
    private function captureInitArguments(): \ArrayObject
    {
        $captured = new \ArrayObject;

        $mock = Mockery::mock('alias:PostHog\PostHog');
        $mock->shouldReceive('init')->once()->andReturnUsing(function (...$arguments) use ($captured) {
            $captured['apiKey'] = $arguments[0] ?? null;
            $captured['options'] = $arguments[1] ?? [];
            $captured['secretKey'] = $arguments['secretKey'] ?? $arguments[4] ?? null;
        });

        return $captured;
    }

    private function instanceUsingPosthog(): object
    {
        return new class
        {
            use UsesPosthog;
        };
    }
}
