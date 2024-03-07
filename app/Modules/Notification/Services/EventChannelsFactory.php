<?php

namespace App\Modules\Notification\Services;

use App\Events\EventInterface;

class EventChannelsFactory
{
    private $eventChannelsSettings;

    /**
     * EventChannelsFactory constructor.
     * @param EventChannelsSettings $eventChannelsSettings
     */
    public function __construct(EventChannelsSettings $eventChannelsSettings)
    {
        $this->eventChannelsSettings = $eventChannelsSettings;
    }

    /**
     * Builder channels.
     *
     * @param EventInterface $event
     * @return array
     */
    public function make(EventInterface $event)
    {
        $availableChannels = $this->eventChannelsSettings->get($event->getType());
        $channels = [];

        foreach ($availableChannels as $channel) {
            switch ($channel) {
                case 'mail':
                    $channels = $this->addMail($channels, $event);
                    break;
                case 'slack':
                    $channels = $this->addSlack($channels, $event);
                    break;
                case 'broadcast':
                    $channels = $this->addBroadcast($channels, $event);
                    break;
            }
        }

        return $channels;
    }

    /**
     * Create mail channel.
     *
     * @param array $channels
     * @param EventInterface $event
     * @return array
     */
    private function addMail(array $channels, EventInterface $event)
    {
        if ($event->getProject()->email_notification_enabled) {
            $channels[] = new EmailChannel(clone $event);
        }

        return $channels;
    }

    /**
     *  Create slack channel.
     *
     * @param array $channels
     * @param EventInterface $event
     * @return array
     */
    private function addSlack(array $channels, EventInterface $event)
    {
        if ($event->getProject()->slack_notification_enabled && $event->getProject()->slack_webhook_url) {
            $channels[] = new SlackChannel(clone $event);
        }

        return $channels;
    }

    /**
     *  Create broadcast channel.
     *
     * @param array $channels
     * @param EventInterface $event
     * @return array
     */
    private function addBroadcast(array $channels, EventInterface $event)
    {
        $channels[] = new BroadcastChannel(clone $event);

        return $channels;
    }
}
