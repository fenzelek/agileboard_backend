<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use App\Notifications\Messages\MailMessage;

class Activation extends Notification
{
    /**
     * Activation url.
     *
     * @var string
     */
    public $url;

    /**
     * SendActivationEmail constructor.
     *
     * @param $url
     */
    public function __construct($url)
    {
        $this->url = $url;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed $notifiable
     *
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed $notifiable
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $url = str_replace(':token', $notifiable->activate_hash, $this->url);

        $translation_package = 'emails.activation.';

        return (new MailMessage())
            ->subject(trans($translation_package . 'subject'))
            ->line(trans($translation_package . 'line_1'))
            ->action(trans($translation_package . 'action'), $url)
            ->line(trans($translation_package . 'line_2'))
            ->regards(trans('emails.default.regards'));
    }
}
