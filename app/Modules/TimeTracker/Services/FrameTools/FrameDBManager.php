<?php

namespace App\Modules\TimeTracker\Services\FrameTools;

use App\Models\Db\TimeTracker\Frame;

class FrameDBManager
{
    private FrameSearcher $frame_searcher;

    public function __construct(FrameSearcher $frame_searcher)
    {
        $this->frame_searcher = $frame_searcher;
    }

    public function searchFrame(Frame $frame)
    {
        $frames = $this->frame_searcher->searchDuplicatesOf($frame);

        if (count($frames)) {
            $frame->delete();
        }
    }
}
