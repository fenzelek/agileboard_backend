<?php

namespace App\Modules\Gantt\Services;

use Carbon\Carbon;
use App\Modules\Gantt\Services\Interfaces\PeriodCalc;

class SprintPeriodCalc implements PeriodCalc
{
    /**
     * @param int $hours
     * @param int $daily_hours
     * @return int
     */
    public function calcDays(int $hours, int $daily_hours = 8): int
    {
        return round($hours / $daily_hours) ?: 1;
    }

    /**
     * @param Carbon $start_date
     * @param int $days_count
     * @return Carbon
     */
    public function calcEndDate(Carbon $start_date, int $days_count): Carbon
    {
        return $start_date->copy()->addWeekdays($days_count);
    }
}
