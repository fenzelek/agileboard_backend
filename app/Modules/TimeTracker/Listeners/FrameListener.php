<?php

namespace App\Modules\TimeTracker\Listeners;

use App\Modules\Integration\Events\UselessFrameWasDetected;
use App\Modules\TimeTracker\Services\FrameTools\FrameDBManager;

class FrameListener
{
    private FrameDBManager $frame_data_base_searcher;

    public function __construct(FrameDBManager $frame_data_base_searcher)
    {
        $this->frame_data_base_searcher = $frame_data_base_searcher;
    }

    public function handle(UselessFrameWasDetected $event)
    {
        $this->frame_data_base_searcher->searchFrame($event->getFrame());
    }
}
