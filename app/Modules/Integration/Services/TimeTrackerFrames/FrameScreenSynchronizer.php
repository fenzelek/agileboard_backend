<?php

namespace App\Modules\Integration\Services\TimeTrackerFrames;

use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\TimeTracker\ActivityFrameScreen;
use App\Models\Db\TimeTracker\Frame;

class FrameScreenSynchronizer
{
    public function sync(Activity $activity, Frame $frame):void
    {
        $screens = $frame->screens()->get();

        foreach ($screens as $screen) {
            $activity_frame_screen = new ActivityFrameScreen();
            $activity_frame_screen->screen_id = $screen->getRelation('screen')->id;

            $activity->screens()->save($activity_frame_screen);
        }
    }
}
