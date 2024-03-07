<?php

namespace App\Modules\Integration\Services\Factories;

use App\Models\Db\User;
use App\Modules\Integration\Services\ManualActivityTools\ManualOwnActivityValidator;
use App\Modules\Integration\Services\ManualRemoveActivityManager;

class ManualRemoveOwnActivityManagerFactory extends CommonRemoveActivityManagerFactory
{
    public function create(User $user):ManualRemoveActivityManager
    {
        $activity_validator = $this->makeActivityValidator();
        $activity_validator->forUser($user);

        return $this->makeRemoveActivityManager($activity_validator);
    }

    private function makeActivityValidator():ManualOwnActivityValidator
    {
        return $this->app->make(ManualOwnActivityValidator::class);
    }
}
