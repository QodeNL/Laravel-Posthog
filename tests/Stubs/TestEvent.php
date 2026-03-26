<?php

namespace QodeNL\LaravelPosthog\Tests\Stubs;

class TestEvent
{
    public function __construct(public TestModel $model) {}
}
