<?php

namespace App\Modules\Agile\Events;

use App\Helpers\BroadcastChannels;
use App\Helpers\EventTypes;
use App\Models\Db\Project;
use App\Models\Db\Ticket;

class ChangePriorityTicketEvent extends AbstractTicketEvent
{
    public $sprint_old_id;
    public $sprint_new_id;

    public function __construct(Project $project, Ticket $ticket, $sprint_old_id, $sprint_new_id)
    {
        $this->project = $project;
        $this->ticket = $ticket;
        $this->sprint_old_id = $sprint_old_id;
        $this->sprint_new_id = $sprint_new_id;
    }

    public function getMessage(): array
    {
        return [];
    }

    public function getRecipients()
    {
        return [];
    }

    public function getType(): string
    {
        return EventTypes::TICKET_CHANGE_PRIORITY;
    }

    public function getBroadcastChannel(): string
    {
        return BroadcastChannels::TICKET_CHANGE_PRIORITY;
    }

    public function getBroadcastData(): array
    {
        return [
            'project_id' => $this->project->id,
            'ticket_id' => $this->ticket->id,
            'sprint_old' => $this->sprint_old_id,
            'sprint_new' => $this->sprint_new_id,
        ];
    }
}
