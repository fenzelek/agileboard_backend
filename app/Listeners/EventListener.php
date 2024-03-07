<?php

namespace App\Listeners;

use App\Events\EventInterface;
use App\Modules\Notification\Services\EventChannelsFactory;
use App\Modules\Notification\Services\NotificationManager;
use Illuminate\Contracts\Auth\Guard;

class EventListener
{
    private $guard;
    private $eventChannelsFactory;

    /**
     * NotificationManager constructor.
     * @param Guard $guard
     * @param EventChannelsFactory $eventChannelsFactory
     */
    public function __construct(Guard $guard, EventChannelsFactory $eventChannelsFactory)
    {
        $this->guard = $guard;
        $this->eventChannelsFactory = $eventChannelsFactory;
    }

    /**
     * Handle the event.
     *
     * @param  EventInterface $event
     *
     * @return void
     */
    public function handle(EventInterface $event)
    {
        (new NotificationManager($this->guard, $this->eventChannelsFactory))->proceed($event);
    }
}
