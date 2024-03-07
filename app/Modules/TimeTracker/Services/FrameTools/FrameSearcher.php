<?php

namespace App\Modules\TimeTracker\Services\FrameTools;

use App\Models\Db\TimeTracker\Frame;

class FrameSearcher
{
    private Frame $frame;

    public function __construct(Frame $frame)
    {
        $this->frame = $frame;
    }

    /**
     * @param Frame $frame
     * @return Frame[]
     */
    public function searchDuplicatesOf($frame): array
    {
        return $this->frame->newModelQuery()
            ->where('id', '!=', $frame->id)
            ->whereUserId($frame->user_id)
            ->where('from', '=', $frame->from)
            ->where('to', '=', $frame->to)
            ->get()
            ->all();
    }
}
