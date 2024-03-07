<?php

namespace App\Modules\Agile\Events;

use App\Helpers\BroadcastChannels;
use App\Helpers\EventTypes;
use App\Models\Db\Role;
use App\Models\Other\RoleType;

class CreateTicketEvent extends AbstractTicketEvent
{
    public function getMessage(): array
    {
        return $this->generateMessage();
    }

    public function getRecipients()
    {
        return $this->project->users()->whereIn('project_user.role_id', [
            Role::findByName(RoleType::OWNER)->id,
            Role::findByName(RoleType::ADMIN)->id,
        ])->get();
    }

    public function getType(): string
    {
        return EventTypes::TICKET_STORE;
    }

    public function getBroadcastChannel(): string
    {
        return BroadcastChannels::TICKET_STORE;
    }

    public function getBroadcastData(): array
    {
        return [
            'project_id' => $this->project->id,
            'ticket_id' => $this->ticket->id,
            'sprint_id' => $this->ticket->sprint_id,
        ];
    }
}
