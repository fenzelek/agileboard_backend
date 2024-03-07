<?php

namespace App\Modules\Company\Notifications;

use App\Models\Db\CompanyModule;
use Illuminate\Notifications\Notification;
use App\Notifications\Messages\MailMessage;

class RenewSubscriptionInformation extends Notification
{
    public $companyModule;
    public $days;

    /**
     * RenewSubscriptionInformation constructor.
     * @param CompanyModule $companyModule
     * @param $days
     */
    public function __construct(CompanyModule $companyModule, $days)
    {
        $this->companyModule = $companyModule;
        $this->days = $days;
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
        $translation_package = 'emails.renew_subscription_information.';

        return (new MailMessage())
            ->subject(trans($translation_package . 'subject'))
            ->line(trans($translation_package . 'line_1_' . $this->days))
            ->regards(trans('emails.default.regards'));
    }
}
