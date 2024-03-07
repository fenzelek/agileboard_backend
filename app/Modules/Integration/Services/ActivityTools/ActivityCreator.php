<?php

namespace App\Modules\Integration\Services\ActivityTools;

use App\Models\Db\Integration\Integration;
use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\TimeTracker\Frame;

class ActivityCreator
{
    private const PREFIX = 'tt';
    private Activity $activity;

    public function __construct(Activity $activity)
    {
        $this->activity = $activity;
    }

    public function create(Frame $frame, Integration $integration):Activity
    {
        $activity = $this->activity->newInstance();
        $activity->integration()->associate($integration);
        $activity->user()->associate($frame->user);
        $activity->project()->associate($frame->project);
        $activity->ticket()->associate($frame->ticket);
        $activity->external_activity_id = self::PREFIX . $frame->id;
        $activity->time_tracking_project_id = null;
        $activity->time_tracking_user_id = null;
        $activity->time_tracking_note_id = null;
        $activity->utc_started_at = $frame->from;
        $activity->utc_finished_at = $frame->to;
        $activity->tracked = $frame->to->getTimestamp() - $frame->from->getTimestamp();
        $activity->activity = $frame->activity;

        return $activity;
    }
}
