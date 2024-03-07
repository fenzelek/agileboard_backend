<?php

namespace App\Modules\TimeTracker\Services;

use App\Models\Db\Integration\TimeTracking\Activity;
use App\Modules\TimeTracker\Services\Contracts\ITimeAdder;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TimeAdder implements ITimeAdder
{
    public function sumTimeInActivities(Collection $activities, Carbon $date): Collection
    {
        return $activities->each(function (Activity $activity) use ($date) {
            $start_of_day = $date->clone()->startOfDay();
            $end_of_day = $date->clone()->endOfDay();
            $started_at = Carbon::parse($activity->utc_started_at);
            $modified = false;
            if (($started_at->lt($start_of_day))) {
                $started_at = $start_of_day;
                $modified = true;
            }
            $finished_at = Carbon::parse($activity->utc_finished_at);
            if (($finished_at->gt($end_of_day))) {
                $finished_at = $end_of_day;
                $modified = true;
            }
            if ($modified) {
                $activity->tracked = $this->customiseActivity($started_at, $finished_at);
            }
        });
    }

    private function getTimestamp(Carbon $date):int
    {
        return $date->timestamp;
    }

    private function customiseActivity($started_at, $finished_at): int
    {
        $started_timestamp = $this->getTimestamp($started_at);
        $finished_timestamp = $this->getTimestamp($finished_at);

        return $finished_timestamp - $started_timestamp;
    }
}
