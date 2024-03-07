<?php

namespace App\Notifications;

use App\Modules\Notification\Services\Mailable;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Collection;

class DailyTicketReport extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @var Collection
     */
    private $report_data;
    /**
     * @var Carbon
     */
    private $date;
    /**
     * @var Collection
     */
    private $project_statuses;

    /**
     * Create a new notification instance.
     *
     * @param Collection $report_data
     * @param Collection $statuses
     * @param Carbon $date
     */
    public function __construct(array $report_data, array $statuses, Carbon $date)
    {
        $this->report_data = $report_data;
        $this->project_statuses = $statuses;
        $this->date = $date;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return Mailable|MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage())
            ->view(
                'emails.reports.daily-ticket-report',
                [
                'user' => $notifiable,
                'report_data' => $this->report_data,
                'project_statuses' => $this->project_statuses,
                'date' => $this->date,
                ]
            )->subject(trans('notifications.daily_ticket_report.subject'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
