<?php

namespace App\Modules\Agile\Events;

use App\Helpers\BroadcastChannels;
use App\Helpers\EventTypes;
use App\Models\Db\Sprint;
use App\Models\Db\Project;

class CloseSprintEvent extends AbstractSprintEvent
{
    public $destination_sprint_id;

    public function __construct(Project $project, Sprint $sprint, $destination_sprint_id)
    {
        parent::__construct($project, $sprint);
        $this->destination_sprint_id = $destination_sprint_id;
    }

    public function getType(): string
    {
        return EventTypes::SPRINT_CLOSE;
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
            'destination_sprint_id' => $this->destination_sprint_id,
            'status' => Sprint::CLOSED,
        ];
    }
}
