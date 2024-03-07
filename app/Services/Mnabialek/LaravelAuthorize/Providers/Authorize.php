<?php

namespace App\Services\Mnabialek\LaravelAuthorize\Providers;

use Illuminate\Support\ServiceProvider;

class Authorize extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../publish/config/authorize.php',
            'authorize'
        );

        // bind implementations to interfaces
        $bindings = $this->app->config->get('authorize')['bindings'];
        foreach ($bindings as $interface => $class) {
            $this->app->bind($interface, $class);
        }

        // register files to be published
        $this->publishes($this->getFilesToPublish());
    }

    /**
     * Get files that will be published.
     *
     * @return array
     */
    protected function getFilesToPublish()
    {
        return [
            __DIR__ . '/../publish/config/authorize.php' => config_path('authorize.php'),
            __DIR__ . '/../publish/Middleware/Authorize.php' => app_path('Http/Middleware/Authorize.php'),
            __DIR__ . '/../publish/views/401.blade.php' => resource_path('views/errors/401.blade.php'),
            __DIR__ . '/../publish/Policies/BasePolicyController.php' => app_path('Policies/BasePolicyController.php'),
        ];
    }
}
