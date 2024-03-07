<?php

namespace App\Notifications;

use App\Modules\Notification\Services\Mailable;
use Illuminate\Notifications\Notification;

class CreatedNewUser extends Notification
{
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
     * @param $notifiable
     * @return mixed
     */
    public function toMail($notifiable)
    {
        $mail = new Mailable();

        $mail->to(config('mail.notify_email'));

        return $mail->subject('Nowy użytkownik')
            ->view('emails.notifications.email')->with([
                'level' => 'default',
                'introLines' => [
                    'Nowy użytkownik:',
                ],
                'outroLines' => [
                    $notifiable->email,
                    'Kod rabatowy:' . $notifiable->discount_code ?: '',
                ],
            ]);
    }
}
