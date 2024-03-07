<?php

namespace App\Modules\Integration\Services\ActivityTools;

use App\Models\Db\Integration\TimeTracking\Activity;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ActivityMerger
{
    private ActivitySearch $activity_search;

    public function __construct(ActivitySearch $activity_search)
    {
        $this->activity_search = $activity_search;
    }

    public function merge(Activity $activity):Activity
    {
        $overlap_activities = $this->activity_search->lookupOverLap($activity);

        if (! count($overlap_activities)) {
            $activity->save();

            return $activity;
        }

        Collection::make($overlap_activities)->each(function (Activity $overlap) use ($activity) {
            if ($overlap->utc_started_at <= $activity->utc_started_at) {
                $activity->utc_started_at = $overlap->utc_finished_at;

                return;
            }
            if ($overlap->utc_finished_at >= $activity->utc_finished_at) {
                $activity->utc_finished_at = $overlap->utc_started_at;

                return;
            }
            $overlap->delete();
        });

        if ($this->hasInputActivity($activity)) {
            $this->customiseActivity($activity);
            $activity->save();
        }

        return $activity;
    }

    private function hasInputActivity(Activity $activity):bool
    {
        $started_timestamp = $this->getTimestamp($activity->utc_started_at);
        $finished_timestamp = $this->getTimestamp($activity->utc_finished_at);

        return ($finished_timestamp - $started_timestamp) > 0;
    }

    private function getTimestamp(Carbon $date):int
    {
        return $date->timestamp;
    }

    /**
     * @param Activity $activity
     */
    private function customiseActivity(Activity $activity): void
    {
        $started_timestamp = $this->getTimestamp($activity->utc_started_at);
        $finished_timestamp = $this->getTimestamp($activity->utc_finished_at);
        $customise_tracked = $finished_timestamp - $started_timestamp;
        $customise_active = (int) ceil(($customise_tracked * $activity->activity) / $activity->tracked);

        $activity->tracked = $customise_tracked;
        $activity->activity = $customise_active;
    }
}
