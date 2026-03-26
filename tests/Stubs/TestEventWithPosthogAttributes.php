<?php

namespace QodeNL\LaravelPosthog\Tests\Stubs;

class TestEventWithPosthogAttributes
{
    public function __construct(public TestModelWithPosthogAttributes $model) {}
}
