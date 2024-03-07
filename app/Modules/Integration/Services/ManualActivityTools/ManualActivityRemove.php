<?php

namespace App\Modules\Integration\Services\ManualActivityTools;

use App\Models\Db\Integration\TimeTracking\Activity;
use App\Modules\Integration\Services\Contracts\RemoveActivityProvider;

class ManualActivityRemove
{
    private Activity $activity;

    /**
     * @param Activity $activity
     */
    public function __construct(Activity $activity)
    {
        $this->activity = $activity;
    }

    public function remove(RemoveActivityProvider $activity_data_provider)
    {
        $activity_ids = $activity_data_provider->getActivitiesIds();

        $this->activity::destroy($activity_ids);
    }
}
