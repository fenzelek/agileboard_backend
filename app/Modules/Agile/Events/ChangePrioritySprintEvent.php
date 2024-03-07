<?php

namespace App\Modules\Agile\Events;

use App\Helpers\BroadcastChannels;
use App\Helpers\EventTypes;
use App\Models\Db\Project;

class ChangePrioritySprintEvent extends AbstractSprintEvent
{
    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    public function getType(): string
    {
        return EventTypes::SPRINT_CHANGE_PRIORITY;
    }

    public function getBroadcastChannel(): string
    {
        return BroadcastChannels::SPRINT_CHANGE_PRIORITY;
    }

    public function getBroadcastData(): array
    {
        return [
            'project_id' => $this->project->id,
        ];
    }
}
