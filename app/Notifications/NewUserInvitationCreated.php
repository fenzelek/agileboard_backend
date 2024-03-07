<?php

namespace App\Notifications;

use App\Models\Db\Company;
use App\Models\Db\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Notifications\Messages\MailMessage;

class NewUserInvitationCreated extends Notification
{
    use Queueable;

    /**
     * @var
     */
    public $url;

    /**
     * @var Company
     */
    public $company;

    /**
     * @var Invitation
     */
    public $invitation;

    /**
     * Create a new notification instance.
     *
     * @param Company $company
     * @param Invitation $invitation
     * @param string $url
     */
    public function __construct(Company $company, Invitation $invitation, $url)
    {
        $this->url = $url;
        $this->company = $company;
        $this->invitation = $invitation;
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
        $company_name = $this->company->name;

        $url = $this->getUrl($notifiable);

        $translation_package = 'emails.new_user_invitation_created.';

        return (new MailMessage())
            ->subject(trans($translation_package . 'subject') . $company_name)
            ->greeting(trans('emails.default.greeting'))
            ->line(trans($translation_package . 'line_1') . $company_name)
            ->action(trans($translation_package . 'action'), $url)
            ->line(trans($translation_package . 'line_2') .
                $this->invitation->expiration_time->format('Y-m-d H:i:s'))
            ->regards($company_name);
    }

    /**
     * Get url that will be put into invitation e-mail.
     *
     * @param mixed $notifiable
     *
     * @return mixed
     */
    public function getUrl($notifiable)
    {
        return str_replace(
            [':email', ':token', ':company'],
            [
                rawurlencode($notifiable->email),
                rawurlencode($this->invitation->unique_hash),
                rawurlencode($this->company->name),
            ],
            $this->url
        );
    }
}
