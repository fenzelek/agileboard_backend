<?php

namespace App\Modules\Integration\Services\TimeTrackerFrames;

use App\Models\Db\Integration\Integration;
use App\Models\Db\TimeTracker\Frame;
use App\Modules\Integration\Events\UselessFrameWasDetected;
use App\Modules\Integration\Services\ActivityTools\ActivityCreator;
use App\Modules\Integration\Services\ActivityTools\ActivityMerger;
use App\Modules\Integration\Services\ActivityTools\ActivitySplitter;
use Illuminate\Database\Connection;
use Illuminate\Events\Dispatcher;
use Psr\Log\LoggerInterface;

class FrameActivityMaker
{
    protected Dispatcher $event_dispatcher;
    protected FrameManager $frame_manager;
    protected ActivityMerger $activity_merger;
    protected FrameScreenSynchronizer $frame_screen_synchronizer;
    private ActivityCreator $activity_creator;
    private ActivitySplitter $activity_splitter;
    protected LoggerInterface $logger;
    protected Connection $db;

    public function __construct(ActivityCreator $activity_creator, FrameManager $frame_manager, ActivityMerger $activity_merger, FrameScreenSynchronizer $frame_screen_synchronizer, Dispatcher $event_dispatcher, ActivitySplitter $activity_splitter, LoggerInterface $logger, Connection $db)
    {
        $this->activity_creator = $activity_creator;
        $this->frame_manager = $frame_manager;
        $this->event_dispatcher = $event_dispatcher;
        $this->activity_merger = $activity_merger;
        $this->frame_screen_synchronizer = $frame_screen_synchronizer;
        $this->activity_splitter = $activity_splitter;
        $this->logger = $logger;
        $this->db = $db;
    }

    public function addActivity(Frame $frame, Integration $integration): void
    {
        try {
            $activity = $this->db->transaction(function () use ($frame, $integration) {

                $activity = $this->activity_creator->create($frame, $integration);

                $merged_activity = $this->activity_merger->merge($activity);

                $splitted_activities = $this->activity_splitter->split($merged_activity);

                $frame = $this->frame_manager->moveToTransformed($frame);

                foreach ($splitted_activities as $activity_item) {
                    $this->frame_screen_synchronizer->sync($activity_item, $frame);
                }
                return $activity;
            });
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), $frame->toArray());
        }

        if ($activity->exists) {

            return;
        }

        $this->event_dispatcher->dispatch(new UselessFrameWasDetected($frame));
    }
}
