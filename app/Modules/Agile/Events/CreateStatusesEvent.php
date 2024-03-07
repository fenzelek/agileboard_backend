<?php

namespace App\Modules\Agile\Events;

use App\Helpers\EventTypes;

class CreateStatusesEvent extends AbstractStatusesEvent
{
    public function getType(): string
    {
        return EventTypes::STATUSES_STORE;
    }
}
