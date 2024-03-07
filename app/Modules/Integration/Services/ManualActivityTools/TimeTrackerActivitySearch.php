<?php

namespace App\Modules\Integration\Services\ManualActivityTools;

use App\Models\Db\Integration\TimeTracking\Activity;
use App\Modules\Integration\Services\Contracts\ManualActivityDataProvider;

class TimeTrackerActivitySearch
{
    private Activity $activity;

    public function __construct(Activity $activity)
    {
        $this->activity = $activity;
    }

    public function lookupOverLap(ManualActivityDataProvider $activity_data_provider): array
    {
        return $this->activity->newModelQuery()
            ->where('user_id', '=', $activity_data_provider->getUserId())
            ->where('utc_started_at', '<=', $activity_data_provider->getTo())
            ->where('utc_finished_at', '>=', $activity_data_provider->getFrom())
             ->whereNull('deleted_at')
            ->orderBy('utc_started_at')
            ->get()
            ->all();
    }
}
