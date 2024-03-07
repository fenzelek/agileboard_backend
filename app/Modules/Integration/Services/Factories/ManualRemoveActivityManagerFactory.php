<?php

namespace App\Modules\Integration\Services\Factories;

use App\Modules\Integration\Services\ManualActivityTools\ManualActivityValidator;
use App\Modules\Integration\Services\ManualRemoveActivityManager;

class ManualRemoveActivityManagerFactory extends CommonRemoveActivityManagerFactory
{
    public function create():ManualRemoveActivityManager
    {
        $activity_validator = $this->app->make(ManualActivityValidator::class);

        return $this->makeRemoveActivityManager($activity_validator);
    }
}
