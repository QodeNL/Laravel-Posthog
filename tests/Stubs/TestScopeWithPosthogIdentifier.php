<?php

namespace QodeNL\LaravelPosthog\Tests\Stubs;

class TestScopeWithPosthogIdentifier
{
    public function getPosthogIdentifier(): string
    {
        return 'custom-scope-id';
    }
}
