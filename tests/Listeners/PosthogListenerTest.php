<?php

namespace QodeNL\LaravelPosthog\Tests\Listeners;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Test;
use QodeNL\LaravelPosthog\Jobs\PosthogCaptureJob;
use QodeNL\LaravelPosthog\LaravelPosthog;
use QodeNL\LaravelPosthog\Listeners\PosthogListener;
use QodeNL\LaravelPosthog\Tests\Stubs\TestEvent;
use QodeNL\LaravelPosthog\Tests\Stubs\TestEventNoModel;
use QodeNL\LaravelPosthog\Tests\Stubs\TestEventWithPosthogAttributes;
use QodeNL\LaravelPosthog\Tests\Stubs\TestModel;
use QodeNL\LaravelPosthog\Tests\Stubs\TestModelWithPosthogAttributes;
use QodeNL\LaravelPosthog\Tests\TestCase;

class PosthogListenerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Auth::shouldReceive('user')->andReturnNull();
        LaravelPosthog::resolveDistinctIdUsing(fn () => 'test-session');
    }

    #[Test]
    public function it_captures_event_with_fillable_minus_hidden_attributes(): void
    {
        Bus::fake();

        $model = new TestModel;
        $model->forceFill(['name' => 'John', 'email' => 'john@example.com', 'status' => 'active']);

        $event = new TestEvent($model);
        $listener = new PosthogListener;
        $listener->handle($event);

        Bus::assertDispatched(PosthogCaptureJob::class, function ($job) {
            $reflEvent = new \ReflectionProperty($job, 'event');
            $reflProps = new \ReflectionProperty($job, 'properties');

            return $reflEvent->getValue($job) === TestEvent::class
                && $reflProps->getValue($job) === ['model' => ['name' => 'John', 'status' => 'active']];
        });
    }

    #[Test]
    public function it_uses_posthog_attributes_when_present(): void
    {
        Bus::fake();

        $model = new TestModelWithPosthogAttributes;
        $model->forceFill(['name' => 'Jane', 'email' => 'jane@example.com', 'status' => 'active', 'role' => 'admin']);

        $event = new TestEventWithPosthogAttributes($model);
        $listener = new PosthogListener;
        $listener->handle($event);

        Bus::assertDispatched(PosthogCaptureJob::class, function ($job) {
            $reflProps = new \ReflectionProperty($job, 'properties');

            return $reflProps->getValue($job) === ['model' => ['name' => 'Jane', 'status' => 'active']];
        });
    }

    #[Test]
    public function it_skips_non_model_constructor_params(): void
    {
        Bus::fake();

        $event = new TestEventNoModel(new \stdClass);
        $listener = new PosthogListener;
        $listener->handle($event);

        Bus::assertDispatched(PosthogCaptureJob::class, function ($job) {
            $reflProps = new \ReflectionProperty($job, 'properties');

            return $reflProps->getValue($job) === [];
        });
    }

    #[Test]
    public function it_uses_event_fqcn_as_event_name(): void
    {
        Bus::fake();

        $event = new TestEventNoModel(new \stdClass);
        $listener = new PosthogListener;
        $listener->handle($event);

        Bus::assertDispatched(PosthogCaptureJob::class, function ($job) {
            $reflEvent = new \ReflectionProperty($job, 'event');

            return $reflEvent->getValue($job) === TestEventNoModel::class;
        });
    }
}
