<?php

namespace App\Modules\Notification\Services;

use Illuminate\Contracts\Auth\Guard;
use Notification;
use App\Events\EventInterface;
use App\Interfaces\EventConsumerInterface;

class NotificationManager implements EventConsumerInterface
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
     * Send notifications.
     *
     * @param EventInterface $event
     */
    public function proceed(EventInterface $event)
    {
        $channels = $this->eventChannelsFactory->make($event);

        foreach ($channels as $channel) {
            Notification::send($channel->getRecipients(), $channel);
        }
    }
}
