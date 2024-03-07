<?php

namespace App\Modules\Agile\Events;

use App\Helpers\BroadcastChannels;
use App\Helpers\EventTypes;

class UpdateSprintEvent extends AbstractSprintEvent
{
    public function getType(): string
    {
        return EventTypes::SPRINT_UPDATE;
    }

    public function getBroadcastChannel(): string
    {
        return BroadcastChannels::SPRINT_UPDATE;
    }

    public function getBroadcastData(): array
    {
        return [
            'project_id' => $this->project->id,
            'sprint' => $this->sprint,
        ];
    }
}
