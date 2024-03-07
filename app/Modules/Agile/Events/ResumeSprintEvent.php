<?php

namespace App\Modules\Agile\Events;

use App\Helpers\BroadcastChannels;
use App\Helpers\EventTypes;
use App\Models\Db\Sprint;

class ResumeSprintEvent extends AbstractSprintEvent
{
    public function getType(): string
    {
        return EventTypes::SPRINT_RESUME;
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
            'status' => Sprint::ACTIVE,
        ];
    }
}
