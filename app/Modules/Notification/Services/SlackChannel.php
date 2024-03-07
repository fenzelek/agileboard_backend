<?php

namespace App\Modules\Notification\Services;

use App\Events\EventInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class SlackChannel extends Notification implements ShouldQueue, NotificationChannelInterface
{
    /**
     * Queue settings.
     */
    public $connection;
    public $queue;
    public $delay;
    private $event;

    public function __construct(EventInterface $event)
    {
        $this->event = $event;

        $this->connection = config('queue.notification.connection');
        $this->queue = config('queue.notification.queue');
        $this->delay = config('queue.notification.delay');
    }

    public function via($notifiable)
    {
        return ['slack'];
    }

    public function getRecipients()
    {
        return $this->event->getProject();
    }

    public function toSlack($notifiable)
    {
        trans()->setLocale($this->event->getProject()->language);

        $message = $this->event->getMessage();

        $return = (new SlackMessage())
            ->success()
            ->from('Agile Board bot')
            ->image(config('app_settings.logo_min'))
//            ->content($message['content'])
            ->attachment(function ($attachment) use ($message) {
                $attachment->title($message['url_title'], $message['url'])
                    ->content($message['content']);
            });

        if ($this->event->getProject()->slack_channel) {
            $return->to('#' . $this->event->getProject()->slack_channel);
        }

        return $return;
    }
}
