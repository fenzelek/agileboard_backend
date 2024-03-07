<?php

namespace Tests\Unit\App\Modules\Integration\Services\TimeTrackerFrames;

use App\Models\Db\Integration\Integration;
use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\TimeTracker\Frame;
use App\Modules\Integration\Services\ActivityTools\ActivityCreator;
use App\Modules\Integration\Services\ActivityTools\ActivityMerger;
use App\Modules\Integration\Services\ActivityTools\ActivitySplitter;
use App\Modules\Integration\Services\TimeTrackerFrames\FrameActivityMaker;
use App\Modules\Integration\Services\TimeTrackerFrames\FrameManager;
use App\Modules\Integration\Services\TimeTrackerFrames\FrameScreenSynchronizer;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery as m;
use Tests\TestCase;
use Event;

class FrameActivityMakerTest extends TestCase
{
    use DatabaseTransactions;
    use FrameActivityMakerTrait;
    /**
     * @var FrameActivityMaker
     */
    private $frame_activity_maker;
    /**
     * @var ActivityCreator|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $activity_creator;
    /**
     * @var FrameManager|m\LegacyMockInterface|m\MockInterface
     */
    private $frame_manager;
    /**
     * @var ActivityMerger|m\LegacyMockInterface|m\MockInterface
     */
    private $activity_merger;
    /**
     * @var Activity|m\LegacyMockInterface|m\MockInterface
     */
    private $activity;
    /**
     * @var Dispatcher|m\LegacyMockInterface|m\MockInterface
     */
    private $event_dispatcher;
    /**
     * @var FrameScreenSynchronizer|m\LegacyMockInterface|m\MockInterface
     */
    private $frame_screen_synchronizer;

    /**
     * @var ActivitySplitter|m\LegacyMockInterface|m\MockInterface
     */
    private $activity_splitter;

    public function setUp():void
    {
        parent::setUp();
        $this->activity_creator = m::mock(ActivityCreator::class);
        $this->frame_manager = m::mock(FrameManager::class);
        $this->activity_merger = m::mock(ActivityMerger::class);
        $this->event_dispatcher = m::mock(Dispatcher::class);
        $this->frame_screen_synchronizer = m::mock(FrameScreenSynchronizer::class);
        $this->activity_splitter = m::mock(ActivitySplitter::class);

        $this->activity = m::mock(Activity::class);
        $this->frame_activity_maker = $this->app->make(FrameActivityMaker::class, [
            'activity_creator' => $this->activity_creator,
            'frame_manager' => $this->frame_manager,
            'activity_merger' => $this->activity_merger,
            'frame_screen_synchronizer' => $this->frame_screen_synchronizer,
            'event_dispatcher' => $this->event_dispatcher,
            'activity_splitter' => $this->activity_splitter,
        ]);
    }

    /**
     * @feature Integration
     * @scenario Save User Activity from Time Tracker
     * @case User Activity was Stored in DB
     *
     * @test
     */
    public function addActivity_new_activity_was_stored()
    {
        //Given
        $frame = m::mock(Frame::class);

        $integration = m::mock(Integration::class);

        //When
        $activity_creator_expectation = $this->whenActivityWasCreated($frame, $integration);
        $activity_merger_expectation = $this->whenActivityWasMerged();
        $activity_split_expectation = $this->whenActivityWasSplitted();

        $frame_manager_expectation = $this->whenFrameWasTransformed($frame);
        $frame_screen_synchronizer_expectation = $this->whenFrameScreenSynchronized($frame);
        $this->activity->exists = true;
        $this->frame_activity_maker->addActivity($frame, $integration);

        //Then
        $activity_creator_expectation->times(1);
        $frame_manager_expectation->times(1);
        $activity_merger_expectation->times(1);
        $activity_split_expectation->times(1);
        $frame_screen_synchronizer_expectation->times(1);
    }

    /**
     * @feature Integration
     * @scenario Save User Activity from Time Tracker
     * @case User Activity not saved when activity already exists
     *
     * @test
     */
    public function addActivity_when_similar_activity_exists_not_saved()
    {
        //Given
        $frame = m::mock(Frame::class);
        $integration = m::mock(Integration::class);

        //When
        $activity_creator_expectation = $this->whenActivityWasCreated($frame, $integration);
        $frame_manager_expectation = $this->whenFrameWasTransformed($frame);
        $activity_merger_expectation = $this->whenActivityWasntMerged();
        $activity_split_expectation = $this->whenActivityWasSplitted();
        $event_dispatcher_expectation = $this->whenEventDispatched();
        $frame_screen_synchronizer_expectation = $this->whenFrameScreenSynchronized($frame);

        $this->frame_activity_maker->addActivity($frame, $integration);

        //Then
        $activity_creator_expectation->times(1);
        $frame_manager_expectation->times(1);
        $activity_merger_expectation->times(1);
        $event_dispatcher_expectation->times(1);
        $frame_screen_synchronizer_expectation->times(1);
        $activity_split_expectation->times(1);
    }
}
