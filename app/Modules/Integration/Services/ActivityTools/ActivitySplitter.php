<?php

namespace App\Modules\Integration\Services\ActivityTools;

use App\Models\Db\Integration\TimeTracking\Activity;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ActivitySplitter
{
    /**
     * @param Activity $activity
     *
     * @return Collection|Activity[]
     */
    public function split(Activity $activity): Collection
    {
        if ($activity->utc_started_at->isSameHour($activity->utc_finished_at) || $this->isLastHour($activity)) {
            return Collection::make([$activity]);
        }
        $cloned = $activity->replicate();
        $cloned->created_at = Carbon::now();
        $activity->utc_finished_at = $activity->utc_started_at->addHour()->startOfHour();
        $cloned->utc_started_at = $activity->utc_finished_at;

        $whole_tracked_time = $activity->utc_started_at->diffInSeconds($cloned->utc_finished_at);
        $whole_activity_time = $activity->activity;

        $activity->tracked = $activity->utc_started_at->diffInSeconds($activity->utc_finished_at);
        $cloned->tracked = $cloned->utc_started_at->diffInSeconds($cloned->utc_finished_at);

        $activity->activity =
            $this->computeActivity($activity, $whole_tracked_time, $whole_activity_time);
        $cloned->activity =
            $this->computeActivity($cloned, $whole_tracked_time, $whole_activity_time);

        $activity->save();
        $cloned->save();

        return Collection::make([$activity])->merge($this->split($cloned));
    }

    private function computeActivity(Activity $activity, float $whole_tracked_time, $whole_activity_time)
    {
        if ($whole_tracked_time <= 0 || $whole_activity_time <= 0) {
            return $activity->activity;
        }
        $activity_tracked_time =
            $activity->utc_started_at->diffInSeconds($activity->utc_finished_at);

        return (int)($activity_tracked_time * $whole_activity_time / $whole_tracked_time);
    }

    private function isLastHour(Activity $activity): bool
    {
        return ($activity->utc_started_at->addHour()->startOfHour() == $activity->utc_finished_at);
    }
}
