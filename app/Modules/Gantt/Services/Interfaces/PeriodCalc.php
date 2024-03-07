<?php

namespace App\Modules\Gantt\Services\Interfaces;

use Carbon\Carbon;

interface PeriodCalc
{
    public function calcDays(int $hours, int $daily_hours = 8): int;

    public function calcEndDate(Carbon $start_date, int $days_count): Carbon;
}
