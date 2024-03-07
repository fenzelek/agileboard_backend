<?php

namespace App\Modules\Gantt\Services\Interfaces;

use Carbon\Carbon;
use App\Models\CustomCollections\UsersCollection;
use App\Models\CustomCollections\ProjectsCollection;

interface WorkloadCalc
{
    public function calc(UsersCollection $workload_users, ProjectsCollection $projects, Carbon $chart_start_date, Carbon $chart_end_date): UsersCollection;
}
