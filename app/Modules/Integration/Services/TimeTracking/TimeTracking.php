<?php

namespace App\Modules\Integration\Services\TimeTracking;

use App\Modules\Integration\Services\Integration;
use App\Modules\Integration\Services\TimeTracking\Contracts\TimeTracking as TimeTrackingContract;
use Carbon\Carbon;

abstract class TimeTracking extends Integration implements TimeTrackingContract
{
    /**
     * @inheritdoc
     */
    public static function getValidationClass()
    {
        return 'App\\Modules\\Integration\\Http\\Requests\\TimeTracking\\' .
            class_basename(static::class);
    }

    /**
     * @inheritdoc
     */
    public function isReadyToRun()
    {
        return $this->startTime()->isPast();
    }

    /**
     * Get start time of time tracking.
     *
     * @return Carbon
     */
    protected function startTime()
    {
        return Carbon::parse($this->settings['start_time'], 'UTC');
    }
}
