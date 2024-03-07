<?php

namespace App\Services\Mnabialek\LaravelAuthorize\Providers;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use App\Services\Mnabialek\LaravelAuthorize\Policies\Gate;

class Auth extends \Illuminate\Auth\AuthServiceProvider
{
    /**
     * @inheritdoc
     */
    protected function registerAccessGate()
    {
        // we register here custom Gate class in order to modify arguments that
        // are passed to policies methods
        $this->app->singleton(GateContract::class, function ($app) {
            return new Gate($app, function () use ($app) {
                return $app['auth']->user();
            });
        });
    }
}
