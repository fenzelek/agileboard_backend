<?php

namespace App\Notifications;

use App\Modules\Notification\Services\Mailable;
use Illuminate\Notifications\Notification;

class PaymentCompleted extends Notification
{
    public $payment;

    /**
     * PaymentCompleted constructor.
     * @param $payment
     */
    public function __construct($payment)
    {
        $this->payment = $payment;
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
     * @param $notifiable
     * @return mixed
     */
    public function toMail($notifiable)
    {
        $history = $this->payment->transaction->companyModulesHistory;

        $outro_lines = [
            'NIP: ' . $history[0]->company->vatin,
            'Package: ' . (count($history) > 1 ? $history[0]->package->name : 'BRAK'),
        ];

        foreach ($history as $item) {
            $outro_lines [] = $item->module->name . ': ' . ($item->new_value === null ? '' : $item->new_value);
        }

        $mail = new Mailable();

        $mail->to(config('mail.notify_email'));

        return $mail->subject('Nowa płatność')
            ->view('emails.notifications.email')->with([
                'level' => 'default',
                'introLines' => [
                    'Nowa płatność:',
                ],
                'outroLines' => $outro_lines,
            ]);
    }
}
