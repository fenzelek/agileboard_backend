<?php

namespace App\Modules\Integration\Services\TimeTrackerFrames;

use App\Models\Db\TimeTracker\Frame;

class FrameManager
{
    public function moveToTransformed(Frame $frame):Frame
    {
        $frame->transformed = true;
        $frame->save();

        return $frame;
    }

    public function setCounterChecksOf(Frame $frame):Frame
    {
        $frame->counter_сhecks += 1;
        $frame->save();

        return $frame;
    }
}
