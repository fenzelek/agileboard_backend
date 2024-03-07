<?php

namespace App\Modules\Gantt\Services\Interfaces;

use Carbon\Carbon;
use App\Models\Db\Project;
use App\Models\Db\Sprint;

interface Workload
{
    public function setStartDate(Carbon $minimum_date);

    public function setEndDate(Carbon $start_date, Carbon $end_date);

    public function setRate(int $rate): int;

    public function withinPeriod(Carbon $from_date, Carbon $to_date): bool;

    public function setWorkloadStart(Carbon $date);

    public function getWorkloadStart(): Carbon;

    public function setWorkloadEnd(Carbon $date);

    public function setProject(Project $project);

    public function setSprint(Sprint $sprint);
}
