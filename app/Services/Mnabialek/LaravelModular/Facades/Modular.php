<?php

namespace App\Services\Mnabialek\LaravelModular\Facades;

use Illuminate\Support\Facades\Facade;

class Modular extends Facade
{
    /**
     * {inheritdoc}.
     */
    protected static function getFacadeAccessor()
    {
        return 'modular';
    }
}
