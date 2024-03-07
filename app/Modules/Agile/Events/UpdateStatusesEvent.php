<?php

namespace App\Modules\Agile\Events;

use App\Helpers\EventTypes;

class UpdateStatusesEvent extends AbstractStatusesEvent
{
    public function getType(): string
    {
        return EventTypes::STATUSES_UPDATE;
    }
}
