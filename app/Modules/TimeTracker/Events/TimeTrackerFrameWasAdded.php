<?php

namespace App\Modules\TimeTracker\Events;

use App\Models\Db\TimeTracker\Frame;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TimeTrackerFrameWasAdded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private Frame $frame;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Frame $frame)
    {
        //
        $this->frame = $frame;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }

    /**
     * @return Frame
     */
    public function getFrame(): Frame
    {
        return $this->frame;
    }
}
