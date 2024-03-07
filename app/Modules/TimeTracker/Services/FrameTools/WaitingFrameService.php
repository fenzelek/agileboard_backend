<?php

namespace App\Modules\TimeTracker\Services\FrameTools;

use App\Models\Db\Integration\IntegrationProvider;
use App\Models\Db\TimeTracker\Frame;
use App\Modules\TimeTracker\Events\TimeTrackerFrameWasAdded;
use Illuminate\Events\Dispatcher;

class WaitingFrameService
{
    private Frame $frame;
    private Dispatcher $event_dispatcher;

    public function __construct(Frame $frame, Dispatcher $event_dispatcher)
    {
        $this->frame = $frame;
        $this->event_dispatcher = $event_dispatcher;
    }

    public function serveUnconvertedFrames()
    {
        $frames = $this->getUnconvertedFrames();

        foreach ($frames as $frame) {
            $this->event_dispatcher->dispatch(new TimeTrackerFrameWasAdded($frame));
        }
    }

    /**
     * @return Frame[]
     */
    public function getUnconvertedFrames(): array
    {
        return $this->frame->newModelQuery()
            ->join('projects', 'time_tracker_frames.project_id', '=', 'projects.id')
            ->join('companies', 'projects.company_id', '=', 'companies.id')
            ->join('integrations', 'companies.id', '=', 'integrations.company_id')
            ->join(
                'integration_providers',
                'integrations.integration_provider_id',
                '=',
                'integration_providers.id'
            )->where('slug', IntegrationProvider::TIME_TRACKER)
            ->where('transformed', '=', false)
            ->where('counter_сhecks', '<', 3)
            ->select('time_tracker_frames.*')
            ->get()
            ->all();
    }
}
