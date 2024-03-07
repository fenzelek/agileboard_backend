<?php

namespace App\Modules\TimeTracker\Services\Contracts;

use App\Modules\TimeTracker\Http\Requests\Contracts\IAddFrames;
use App\Modules\TimeTracker\Models\ProcessResult;
use Carbon\Carbon;
use Illuminate\Support\Collection;

interface ITimeTracker
{
    public function getTimeSummary(Carbon $date, int $time_zone_offset): Collection;

    public function processFrames(IAddFrames $request): ProcessResult;
}
