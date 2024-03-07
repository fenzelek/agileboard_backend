<?php

namespace App\Modules\Integration\Services\ManualActivityTools;

use App\Modules\Integration\Services\Contracts\ManualActivityDataProvider;
use Carbon\Carbon;

class ManualActivityTimeValidator
{
    public function check(ManualActivityDataProvider $activity_data_provider): bool
    {
        if ($this->isMoreThanNow($activity_data_provider) ||
            $this->isInvalidTime($activity_data_provider)) {
            return false;
        }

        return true;
    }

    /**
     * @param ManualActivityDataProvider $activity_data_provider
     *
     * @return bool
     */
    protected function isMoreThanNow(ManualActivityDataProvider $activity_data_provider): bool
    {
        return $activity_data_provider->getTo()->greaterThan(Carbon::now()) ||
            $activity_data_provider->getFrom()->greaterThan(Carbon::now());
    }

    /**
     * @param ManualActivityDataProvider $activity_data_provider
     *
     * @return bool
     */
    protected function isInvalidTime(ManualActivityDataProvider $activity_data_provider): bool
    {
        return $activity_data_provider->getTo() < $activity_data_provider->getFrom();
    }
}
