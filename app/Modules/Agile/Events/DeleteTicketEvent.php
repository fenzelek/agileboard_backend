<?php

namespace App\Modules\Agile\Events;

use App\Helpers\BroadcastChannels;
use App\Helpers\EventTypes;
use App\Models\Db\Role;
use App\Models\Other\RoleType;

class DeleteTicketEvent extends AbstractTicketEvent
{
    public function getMessage(): array
    {
        return $this->generateMessage();
    }

    public function getRecipients()
    {
        $return = $this->project->users()
            ->whereIn('project_user.role_id', [
                Role::findByName(RoleType::ADMIN)->id,
                Role::findByName(RoleType::OWNER)->id,
            ])
            ->get();

        return $this->addRecipient($return, $this->ticket->assignedUser);
    }

    public function getType(): string
    {
        return EventTypes::TICKET_DELETE;
    }

    public function getBroadcastChannel(): string
    {
        return BroadcastChannels::TICKET_DELETE;
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
