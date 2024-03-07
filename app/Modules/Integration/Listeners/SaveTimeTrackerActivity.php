<?php

namespace App\Modules\Integration\Listeners;

use App\Modules\Integration\Services\TimeTrackerFrameIntegrator;
use App\Modules\TimeTracker\Events\TimeTrackerFrameWasAdded;

class SaveTimeTrackerActivity
{
    private TimeTrackerFrameIntegrator $time_tracker_frame_integrator;

    public function __construct(TimeTrackerFrameIntegrator $time_tracker_frame_integrator)
    {
        $this->time_tracker_frame_integrator = $time_tracker_frame_integrator;
    }

    public function handle(TimeTrackerFrameWasAdded $event)
    {
        $this->time_tracker_frame_integrator->addActivity($event->getFrame());
    }
}
