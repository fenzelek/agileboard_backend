<?php

namespace App\Notifications;

use Request;
use Illuminate\Notifications\Notification;
use App\Notifications\Messages\MailMessage;

class ResetPassword extends Notification
{
    /**
     * The password reset token.
     *
     * @var string
     */
    public $token;

    /**
     * Create a notification instance.
     *
     * @param  string $token
     */
    public function __construct($token)
    {
        $this->token = $token;
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
        $url = str_replace(
            [':token', ':email'],
            [
                $this->token,
                urlencode($notifiable->getEmailForPasswordReset()),
            ],
            Request::input('url')
        );

        $translation_package = 'emails.reset_password.';

        return (new MailMessage())
            ->subject(trans($translation_package . 'subject'))
            ->line(trans($translation_package . 'line_1'))
            ->action(trans($translation_package . 'action'), $url)
            ->line(trans($translation_package . 'line_2'))
            ->regards(trans('emails.default.regards'));
    }
}
