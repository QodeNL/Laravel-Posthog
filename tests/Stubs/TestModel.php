<?php

namespace QodeNL\LaravelPosthog\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
    protected $fillable = ['name', 'email', 'status'];

    protected $hidden = ['email'];
}
