<?php

namespace QodeNL\LaravelPosthog\Tests\Stubs;

use Illuminate\Foundation\Auth\User as Authenticatable;

class TestUser extends Authenticatable
{
    protected $guarded = [];
}
