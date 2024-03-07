<?php

namespace App\Modules\Company\Notifications;

use App\Models\Db\CompanyModule;
use Illuminate\Notifications\Notification;
use App\Notifications\Messages\MailMessage;

class SubscriptionCanceled extends Notification
{
    public $companyModule;

    /**
     * RenewSubscriptionGetPayment constructor.
     * @param CompanyModule $companyModule
     */
    public function __construct(CompanyModule $companyModule)
    {
        $this->companyModule = $companyModule;
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
        $translation_package = 'emails.payment_subscription_canceled';

        return (new MailMessage())
            ->subject(trans($translation_package . 'subject'))
            ->line(trans($translation_package . 'line_1'))
            ->regards(trans('emails.default.regards'));
    }
}
