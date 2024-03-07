<?php

namespace App\Modules\Notification\Services;

use Auth;
use App\Events\EventInterface;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class BroadcastChannel extends Notification implements ShouldQueue, NotificationChannelInterface, ShouldBroadcast
{
    use InteractsWithSockets;

    /**
     * Queue settings.
     */
    public $connection;
    public $queue;
    public $delay;

    /**
     * Data.
     *
     * @var array
     */
    public $data;

    /**
     * Events.
     *
     * @var array
     */
    public $events = [];

    /**
     * Event.
     *
     * @var EventInterface
     */
    private $event;

    public function __construct(EventInterface $event)
    {
        $this->event = $event;

        $this->data = [
            'data' => $event->getBroadcastData(),
            'webbrowser_tab_id' => request()->header('WebbrowserTabId', null),
            'sender_id' => Auth::id(),
        ];

        foreach ($event->getProject()->users as $recipient) {
            $this->events[] = 'user.' . $event->getProject()->id . '.' . $recipient->id;
        }

        $this->connection = config('queue.notification.connection');
        $this->queue = config('queue.notification.queue');
        $this->delay = config('queue.notification.delay');
    }

    public function via($notifiable)
    {
        return ['broadcast'];
    }

    public function getRecipients()
    {
        return $this->event->getProject();
    }

    public function broadcastOn()
    {
        return $this->events;
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'data' => $this->data,
            'channel' => $this->event->getBroadcastChannel(),
        ]);
    }
}
