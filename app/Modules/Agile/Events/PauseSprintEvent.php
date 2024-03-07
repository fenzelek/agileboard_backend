<?php

namespace App\Modules\Agile\Events;

use App\Helpers\BroadcastChannels;
use App\Helpers\EventTypes;
use App\Models\Db\Sprint;

class PauseSprintEvent extends AbstractSprintEvent
{
    public function getType(): string
    {
        return EventTypes::SPRINT_PAUSE;
    }

    public function getBroadcastChannel(): string
    {
        return BroadcastChannels::SPRINT_CHANGE_STATUS;
    }

    public function getBroadcastData(): array
    {
        return [
            'project_id' => $this->project->id,
            'sprint_id' => $this->sprint->id,
            'status' => Sprint::PAUSED,
        ];
    }
}
