<?php

namespace App\Providers;

use App\Services\Grimzy\ConnectionFactory;
use Grimzy\LaravelMysqlSpatial\SpatialServiceProvider as OriginalSpatialServiceProvider;

class SpatialServiceProvider extends OriginalSpatialServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        parent::register();

        $this->app->singleton('db.factory', function ($app) {
            // Use our `ConnectionFactory` in order to fix the DB connections issue.
            return new ConnectionFactory($app);
        });
    }
}
