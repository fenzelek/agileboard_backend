<?php

namespace App\Modules\Agile\Events;

use App\Events\AbstractEvent;
use App\Helpers\BroadcastChannels;
use App\Models\Db\Project;

abstract class AbstractStatusesEvent extends AbstractEvent
{
    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    public function getMessage(): array
    {
        return [];
    }

    public function getRecipients()
    {
        return [];
    }

    public function getBroadcastChannel(): string
    {
        return BroadcastChannels::STATUSES_CHANGE;
    }

    public function getBroadcastData(): array
    {
        return [
            'project_id' => $this->project->id,
        ];
    }
}
