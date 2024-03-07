<?php

namespace App\Modules\Integration\Services\ManualActivityTools;

use App\Models\Db\Integration\Integration;
use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\Integration\TimeTracking\ManualActivityHistory;
use App\Modules\Integration\Models\ActivityFromToDTO;
use App\Modules\Integration\Services\Contracts\ManualActivityDataProvider;

class ManualActivityCreator
{
    const PREFIX = 'manual';
    private Activity $activity;

    public function __construct(Activity $activity)
    {
        $this->activity = $activity;
    }

    public function create(ActivityFromToDTO $slot, ManualActivityDataProvider $activity_data_provider, Integration $integration, ManualActivityHistory $history): Activity
    {
        $activity = $this->activity->newInstance();
        $activity->integration()->associate($integration);
        $activity->user_id = $activity_data_provider->getUserId();
        $activity->project_id = $activity_data_provider->getProjectId();
        $activity->ticket_id = $activity_data_provider->getTicketId();
        $activity->external_activity_id = self::PREFIX . $history->id;
        $activity->time_tracking_project_id = null;
        $activity->time_tracking_user_id = null;
        $activity->time_tracking_note_id = null;
        $activity->utc_started_at = $slot->from;
        $activity->utc_finished_at = $slot->to;
        $activity->tracked = $slot->to->getTimestamp() - $slot->from->getTimestamp();
        $activity->activity = 0;
        $activity->comment = $activity_data_provider->getComment();

        $activity->save();

        return $activity;
    }
}
