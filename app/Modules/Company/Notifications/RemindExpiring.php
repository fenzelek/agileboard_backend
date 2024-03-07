<?php

namespace App\Modules\Company\Notifications;

use App\Models\Db\CompanyModule;
use Illuminate\Notifications\Notification;
use App\Notifications\Messages\MailMessage;

class RemindExpiring extends Notification
{
    public $companyModule;
    public $days;

    /**
     * RemindExpiring constructor.
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
        $translation_package = 'emails.remind_expiring_module.';
        if ($this->companyModule->package_id) {
            $translation_package = 'emails.remind_expiring_package.';
        }

        if ($this->days == 1) {
            return (new MailMessage())
                ->subject(trans($translation_package . 'subject_1'))
                ->line(trans($translation_package . 'line_1_1'))
                ->regards(trans('emails.default.regards'));
        }

        return (new MailMessage())
            ->subject(trans($translation_package . 'subject', ['days' => $this->days]))
            ->line(trans($translation_package . 'line_1', ['days' => $this->days]))
            ->regards(trans('emails.default.regards'));
    }
}
