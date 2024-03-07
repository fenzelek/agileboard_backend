<?php

namespace App\Modules\TimeTracker\Services\Contracts;

use Carbon\Carbon;
use Illuminate\Support\Collection;

interface ITimeAdder
{
    public function sumTimeInActivities(Collection $activities, Carbon $date): Collection;
}
