<?php

namespace App\Modules\Agile\Events;

use App\Helpers\BroadcastChannels;
use App\Helpers\EventTypes;

class CreateSprintEvent extends AbstractSprintEvent
{
    public function getType(): string
    {
        return EventTypes::SPRINT_STORE;
    }

    public function getBroadcastChannel(): string
    {
        return BroadcastChannels::SPRINT_STORE;
    }

    public function getBroadcastData(): array
    {
        return [
            'project_id' => $this->project->id,
            'sprint' => $this->sprint,
        ];
    }
}
