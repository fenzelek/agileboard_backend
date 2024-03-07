<?php

namespace App\Modules\TimeTracker\Models;

class ProcessResult
{
    private $rejected_frames;

    /**
     * @param $rejected_frames
     */
    public function __construct($rejected_frames)
    {
        $this->rejected_frames = $rejected_frames;
    }

    public function getRejectedFrames()
    {
        return $this->rejected_frames;
    }
}
