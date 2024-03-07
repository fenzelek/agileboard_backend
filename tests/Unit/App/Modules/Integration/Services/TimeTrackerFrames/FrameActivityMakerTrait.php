<?php

namespace Tests\Unit\App\Modules\Integration\Services\TimeTrackerFrames;

use Mockery as m;

trait FrameActivityMakerTrait
{
    /**
     * @param $frame
     * @param $integration
     *
     * @return m\Expectation|m\ExpectationInterface|m\HigherOrderMessage
     */
    protected function whenActivityWasCreated($frame, $integration)
    {
        $activity_creator_expectation = $this->activity_creator->shouldReceive('create')
            ->with($frame, $integration)
            ->andReturn($this->activity);

        return $activity_creator_expectation;
    }

    /**
     * @param $frame
     *
     * @return m\Expectation|m\ExpectationInterface|m\HigherOrderMessage
     */
    protected function whenFrameWasTransformed($frame)
    {
        return $this->frame_manager->shouldReceive('moveToTransformed')
            ->with($frame)
            ->andReturn($frame);
    }

    /**
     * @return m\Expectation|m\ExpectationInterface|m\HigherOrderMessage
     */
    protected function whenActivityWasMerged()
    {
        $this->activity->exists = true;

        return $this->activity_merger->shouldReceive('merge')
            ->with($this->activity)
            ->andReturn($this->activity);
    }

    /**
     * @return m\Expectation|m\ExpectationInterface|m\HigherOrderMessage
     */
    protected function whenActivityWasntMerged()
    {
        $this->activity->exists = false;

        return $this->activity_merger->shouldReceive('merge')
            ->with($this->activity)
            ->andReturn($this->activity);
    }

    /**
     * @param $frame
     *
     * @return m\Expectation|m\ExpectationInterface|m\HigherOrderMessage
     */
    protected function whenFrameScreenSynchronized($frame)
    {
        $frame_screen_synchronizer_expectation =
            $this->frame_screen_synchronizer->shouldReceive('sync')
                ->with($this->activity, $frame);

        return $frame_screen_synchronizer_expectation;
    }

    /**
     * @param $frame
     *
     * @return m\Expectation|m\ExpectationInterface|m\HigherOrderMessage
     */
    protected function whenEventDispatched()
    {
        $event_dispatcher_expectation =
            $this->event_dispatcher->shouldReceive('dispatch');

        return $event_dispatcher_expectation;
    }

    /**
     * @return m\Expectation|m\ExpectationInterface|m\HigherOrderMessage
     */
    private function whenActivityWasSplitted()
    {
        $this->activity->exists = false;

        return $this->activity_splitter->shouldReceive('split')
            ->with($this->activity)
            ->andReturn(collect([$this->activity]));
    }
}
