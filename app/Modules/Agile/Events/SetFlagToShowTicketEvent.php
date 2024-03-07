<?php

namespace App\Modules\Agile\Events;

use App\Helpers\BroadcastChannels;
use App\Helpers\EventTypes;
use App\Models\Db\Project;
use App\Models\Db\Ticket;

class SetFlagToShowTicketEvent extends AbstractTicketEvent
{
    public $sprint;

    public function __construct(Project $project, Ticket $ticket, $sprint)
    {
        $this->project = $project;
        $this->ticket = $ticket;
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

    public function getType(): string
    {
        return EventTypes::TICKET_SET_SHOW_FLAG;
    }

    public function getBroadcastChannel(): string
    {
        return BroadcastChannels::TICKET_CHANGE_MIN;
    }

    public function getBroadcastData(): array
    {
        return [
            'project_id' => $this->project->id,
            'ticket_id' => $this->ticket->id,
            'sprint_id' => $this->sprint ? $this->sprint->id : 0,
        ];
    }
}
