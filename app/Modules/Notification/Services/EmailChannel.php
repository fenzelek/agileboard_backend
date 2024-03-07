<?php

namespace App\Modules\Notification\Services;

use App\Events\EventInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class EmailChannel extends Notification implements ShouldQueue, NotificationChannelInterface
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
        config(['mail.driver' => config('mail.notification.driver')]);
        config(['mail.encryption' => config('mail.notification.encryption')]);
        config(['mail.host' => config('mail.notification.host')]);
        config(['mail.port' => config('mail.notification.port')]);
        config(['mail.username' => config('mail.notification.username')]);
        config(['mail.password' => config('mail.notification.password')]);
        config(['mail.from' => config('mail.notification.from')]);

        return ['mail'];
    }

    public function getRecipients()
    {
        return $this->event->getRecipients();
    }

    public function toMail($notifiable)
    {
        trans()->setLocale($this->event->getProject()->language);

        $message = $this->event->getMessage();

        $mail = new Mailable();

        $mail->to($notifiable->email, $notifiable->first_name . ' ' . $notifiable->last_name);

        return $mail->subject($message['title'])
            ->view('emails.notifications.email')->with([
                'level' => 'default',
                'introLines' => [
                    $message['content'],
                ],
                'actionText' => $message['url_title'],
                'actionUrl' => $message['url'],
                'outroLines' => [],
            ]);
    }
}
