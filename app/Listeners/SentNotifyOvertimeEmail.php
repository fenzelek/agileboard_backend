<?php

namespace App\Listeners;

use App\Modules\CalendarAvailability\Events\OvertimeWasAdded;
use App\Notifications\OvertimeAdded;

class SentNotifyOvertimeEmail
{
    /**
     * Handle the event.
     *
     * @param  OvertimeWasAdded $event
     *
     * @return void
     */
    public function handle(OvertimeWasAdded $event)
    {
        $users = $event->getMailableUsers();

        try {
            foreach ($users as $user) {
                $user->notify(new OvertimeAdded($event->process_user));
            }
        } catch (\Exception $exception) {
            \Log::error([get_class($exception), $exception->getMessage()]);
        }
    }
}
