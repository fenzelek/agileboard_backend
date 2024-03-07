<?php

namespace App\Providers;

use App\Modules\Integration\Events\UselessFrameWasDetected;
use App\Modules\Integration\Listeners\SaveTimeTrackerActivity;
use App\Modules\TimeTracker\Events\TimeTrackerFrameWasAdded;
use App\Modules\TimeTracker\Listeners\FrameListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        \App\Modules\User\Events\UserWasCreated::class => [
            \App\Listeners\SendActivationEmail::class,
            \App\Listeners\SendNotifyEmailUserWasCreated::class,
        ],
        \App\Modules\User\Events\UserWasActivated::class => [

        ],
        \App\Modules\User\Events\UserWasAssignedToCompany::class => [
        ],
        \App\Modules\User\Events\ActivationTokenWasRequested::class => [
            \App\Listeners\SendActivationEmail::class,
        ],
        \App\Modules\CashFlow\Events\ReceiptWasCreated::class => [
            \App\Listeners\CreateReceiptCashFlow::class,
        ],

        \App\Modules\Project\Events\AssignedEvent::class => [
            \App\Listeners\EventListener::class,
        ],
        \App\Modules\Agile\Events\CreateSprintEvent::class => [
            \App\Listeners\EventListener::class,
        ],
        \App\Modules\Agile\Events\UpdateSprintEvent::class => [
            \App\Listeners\EventListener::class,
        ],
        \App\Modules\Agile\Events\ActiveSprintEvent::class => [
            \App\Listeners\EventListener::class,
        ],
        \App\Modules\Agile\Events\PauseSprintEvent::class => [
            \App\Listeners\EventListener::class,
        ],
        \App\Modules\Agile\Events\ResumeSprintEvent::class => [
            \App\Listeners\EventListener::class,
        ],
        \App\Modules\Agile\Events\CloseSprintEvent::class => [
            \App\Listeners\EventListener::class,
        ],
        \App\Modules\Agile\Events\DeleteSprintEvent::class => [
            \App\Listeners\EventListener::class,
        ],
        \App\Modules\Agile\Events\ChangePrioritySprintEvent::class => [
            \App\Listeners\EventListener::class,
        ],
        \App\Modules\Agile\Events\CreateTicketEvent::class => [
            \App\Listeners\EventListener::class,
        ],
        \App\Modules\Agile\Events\DeleteTicketEvent::class => [
            \App\Listeners\EventListener::class,
            \App\Modules\Agile\Listeners\RealizationTicketListener::class,
        ],
        \App\Modules\Agile\Events\ChangePriorityTicketEvent::class => [
            \App\Listeners\EventListener::class,
        ],
        \App\Modules\Agile\Events\UpdateTicketEvent::class => [
            \App\Listeners\EventListener::class,
        ],
        \App\Modules\Agile\Events\SetFlagToShowTicketEvent::class => [
            \App\Listeners\EventListener::class,
        ],
        \App\Modules\Agile\Events\SetFlagToHideTicketEvent::class => [
            \App\Listeners\EventListener::class,
        ],
        \App\Modules\Agile\Events\AssignedTicketEvent::class => [
            \App\Listeners\EventListener::class,
            \App\Modules\Agile\Listeners\RealizationTicketListener::class,
        ],
        \App\Modules\Agile\Events\MoveTicketEvent::class => [
            \App\Listeners\EventListener::class,
            \App\Modules\Agile\Listeners\RealizationTicketListener::class,
        ],
        \App\Modules\Agile\Events\ExpiredScheduledDateEvent::class => [
            \App\Listeners\EventListener::class,
        ],
        \App\Modules\Agile\Events\TodayScheduledDateEvent::class => [
            \App\Listeners\EventListener::class,
        ],
        \App\Modules\Agile\Events\CreateCommentEvent::class => [
            \App\Listeners\EventListener::class,
        ],
        \App\Modules\Agile\Events\UpdateCommentEvent::class => [
            \App\Listeners\EventListener::class,
        ],
        \App\Modules\Agile\Events\DeleteCommentEvent::class => [
            \App\Listeners\EventListener::class,
        ],
        \App\Modules\Agile\Events\CreateStatusesEvent::class => [
            \App\Listeners\EventListener::class,
        ],
        \App\Modules\Agile\Events\UpdateStatusesEvent::class => [
            \App\Listeners\EventListener::class,
        ],
        \App\Modules\Company\Events\PaymentCompleted::class => [
            \App\Listeners\SendPaymentCompletedToSuperAdmin::class,
        ],
        TimeTrackerFrameWasAdded::class => [
            SaveTimeTrackerActivity::class,
        ],
        UselessFrameWasDetected::class => [
            FrameListener::class,
        ],
        \App\Modules\CalendarAvailability\Events\OvertimeWasAdded::class => [
            \App\Listeners\SentNotifyOvertimeEmail::class,
        ],
    ];

    /**
     * Register any other events for your application.
     */
    public function boot()
    {
        parent::boot();
    }
}
