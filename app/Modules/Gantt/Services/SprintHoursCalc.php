<?php

namespace App\Modules\Gantt\Services;

use App\Models\Db\Sprint;
use App\Modules\Gantt\Services\Interfaces\HoursCalc;

class SprintHoursCalc implements HoursCalc
{
    /**
     * @param Sprint $sprint
     * @return int
     */
    public function calc(Sprint $sprint): int
    {
        $seconds = $sprint->tickets->sum('estimate_time');

        return $this->secondsToHours($seconds);
    }

    /**
     * @param $estimation_seconds
     * @return float
     */
    private function secondsToHours($estimation_seconds): float
    {
        return round($estimation_seconds / 60 / 60, 2);
    }
}
