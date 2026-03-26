<?php

namespace QodeNL\LaravelPosthog\Tests\Stubs;

class TestEventNoModel
{
    public function __construct(public \stdClass $data) {}
}
