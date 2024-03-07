<?php

namespace App\Modules\Company\Notifications;

use App\Models\Db\Payment;
use App\Models\Other\PaymentStatus;
use Illuminate\Notifications\Notification;
use App\Notifications\Messages\MailMessage;

class PaymentStatusInfo extends Notification
{
    public $payment;

    /**
     * PaymentStatusInfo constructor.
     * @param Payment $payment
     */
    public function __construct(Payment $payment)
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
     * @param  mixed $notifiable
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $translation_package = 'emails.payment_status_info_completed.';

        if ($this->payment->status == PaymentStatus::STATUS_CANCELED) {
            $translation_package = 'emails.payment_status_info_canceled.';
        }

        return (new MailMessage())
            ->subject(trans($translation_package . 'subject'))
            ->line(trans($translation_package . 'line_1', ['number' => $this->payment->id]))
            ->regards(trans('emails.default.regards'));
    }
}
