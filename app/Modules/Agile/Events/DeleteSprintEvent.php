<?php

namespace App\Modules\Agile\Events;

use App\Helpers\BroadcastChannels;
use App\Helpers\EventTypes;
use App\Models\Db\Project;
use App\Models\Db\Sprint;

class DeleteSprintEvent extends AbstractSprintEvent
{
    public function __construct(Project $project, Sprint $sprint)
    {
        $this->project = $project;
        $this->sprint = $sprint->id;
    }

    public function getType(): string
    {
        return EventTypes::SPRINT_DELETE;
    }

    public function getBroadcastChannel(): string
    {
        return BroadcastChannels::SPRINT_DELETE;
    }

    public function getBroadcastData(): array
    {
        return [
            'project_id' => $this->project->id,
            'sprint_id' => $this->sprint,
        ];
    }
}
