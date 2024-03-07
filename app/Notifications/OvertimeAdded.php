<?php

namespace App\Notifications;

use App\Models\Db\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Notifications\Messages\MailMessage;
use function trans;

class OvertimeAdded extends Notification implements ShouldQueue
{
    use Queueable;

    private User $user;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
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
     * @param mixed $notifiable
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $overtime_information = 'emails.overtime_information.';

        return (new MailMessage())
            ->subject(trans($overtime_information . 'subject'))
            ->line(trans($overtime_information . 'line_1') . $this->user->first_name . ' ' .
                $this->user->last_name)
            ->line(trans($overtime_information . 'line_2'))
            ->regards(trans('emails.default.regards'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     *
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
