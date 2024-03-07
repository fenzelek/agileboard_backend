<?php

namespace App\Modules\Agile\Events;

use App\Helpers\BroadcastChannels;
use App\Helpers\EventTypes;

class LockSprintEvent extends AbstractSprintEvent
{
    public function getType(): string
    {
        return EventTypes::SPRINT_LOCK;
    }

    public function getBroadcastChannel(): string
    {
        return BroadcastChannels::SPRINT_LOCK;
    }

    public function getBroadcastData(): array
    {
        return [
            'project_id' => $this->project->id,
            'sprint_id' => $this->sprint->id,
            'locked' => true,
        ];
    }
}
