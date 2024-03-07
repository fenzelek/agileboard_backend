<?php

namespace App\Modules\CalendarAvailability\Services\AvailabilityTools;

use App\Models\Db\User;
use App\Modules\CalendarAvailability\Contracts\NotificationOvertimeServiceInterface;
use App\Modules\CalendarAvailability\Events\OvertimeWasAdded;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Collection;

class NotificationOvertimeOwnService implements NotificationOvertimeServiceInterface
{
    protected Dispatcher $event_dispatcher;

    /**
     * @param Dispatcher $event_dispatcher
     */
    public function __construct(Dispatcher $event_dispatcher)
    {
        $this->event_dispatcher = $event_dispatcher;
    }

    private function hasOvertime(Collection $availabilities): bool
    {
        return $availabilities->contains('overtime', true);
    }

    public function notify(Collection $availabilities, User $process_user): void
    {
        if ($this->hasOvertime($availabilities)) {

            // Dispatch overtime event
            //TODO overtime
            //Commented by Dominic`s decision 6.04.22
            //$this->event_dispatcher->dispatch(new OvertimeWasAdded($process_user));
        }
    }
}
