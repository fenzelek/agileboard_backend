<?php

namespace App\Modules\Gantt\Services\Interfaces;

use App\Models\Db\Sprint;

interface HoursCalc
{
    public function calc(Sprint $sprint): int;
}
