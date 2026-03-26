<?php

namespace QodeNL\LaravelPosthog\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;

class TestModelWithPosthogAttributes extends Model
{
    protected $fillable = ['name', 'email', 'status', 'role'];

    public array $posthogAttributes = ['name', 'status'];
}
