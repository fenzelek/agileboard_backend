<?php

namespace App\Modules\Agile\Events;

use App\Events\AbstractEvent;
use App\Models\Db\Project;
use App\Models\Db\Sprint;

abstract class AbstractSprintEvent extends AbstractEvent
{
    public $sprint;

    public function __construct(Project $project, Sprint $sprint)
    {
        $this->project = $project;
        $this->sprint = $sprint;
    }

    public function getMessage(): array
    {
        return [];
    }

    public function getRecipients()
    {
        return [];
    }
}
