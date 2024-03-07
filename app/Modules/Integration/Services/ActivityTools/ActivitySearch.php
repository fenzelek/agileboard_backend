<?php

namespace App\Modules\Integration\Services\ActivityTools;

use App\Models\Db\Integration\TimeTracking\Activity;

class ActivitySearch
{
    private Activity $activity;

    public function __construct(Activity $activity)
    {
        $this->activity = $activity;
    }

    /**
     * @param Activity $activity
     *
     * @return Activity[]
     */
    public function lookupOverLap(Activity $activity): array
    {
        return $this->activity->newModelQuery()
            ->whereUserId($activity->user_id)
            ->where('utc_started_at', '<=', $activity->utc_finished_at)
            ->where('utc_finished_at', '>=', $activity->utc_started_at)
            ->orderBy('utc_started_at')
            ->get()
            ->all();
    }
}
