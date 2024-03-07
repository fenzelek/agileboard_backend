<?php

namespace App\Modules\Integration\Services\Factories;

use App\Modules\Integration\Services\Contracts\ManualActivityValidator;
use App\Modules\Integration\Services\ManualRemoveActivityManager;
use Illuminate\Contracts\Container\Container;

abstract class CommonRemoveActivityManagerFactory
{
    protected Container $app;

    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    protected function makeRemoveActivityManager(ManualActivityValidator $activity_validator):ManualRemoveActivityManager
    {
        return $this->app->make(ManualRemoveActivityManager::class, [
            'activity_validator' => $activity_validator,
        ]);
    }
}
