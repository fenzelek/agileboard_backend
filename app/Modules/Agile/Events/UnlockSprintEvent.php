<?php

namespace App\Modules\Agile\Events;

use App\Helpers\BroadcastChannels;
use App\Helpers\EventTypes;

class UnlockSprintEvent extends AbstractSprintEvent
{
    public function getType(): string
    {
        return EventTypes::SPRINT_UNLOCK;
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
            'locked' => false,
        ];
    }
}
