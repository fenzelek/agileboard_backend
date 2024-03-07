<?php

namespace App\Modules\Agile\Events;

use App\Helpers\EventTypes;
use App\Models\Db\Role;
use App\Models\Other\RoleType;

class MoveTicketEvent extends AbstractTicketEvent
{
    public function getMessage(): array
    {
        return $this->generateMessage(['status_name' => $this->ticket->status->name]);
    }

    public function getRecipients()
    {
        $return = $this->project->users()
            ->whereIn('project_user.role_id', [
                Role::findByName(RoleType::ADMIN)->id,
                Role::findByName(RoleType::OWNER)->id,
            ])
            ->get();
        $return = $this->addRecipient($return, $this->ticket->assignedUser);
        $return = $this->addRecipient($return, $this->ticket->reportingUser);

        return $return;
    }

    public function getType(): string
    {
        return EventTypes::TICKET_MOVE;
    }

    public function getBroadcastChannel(): string
    {
        return '';
    }

    public function getBroadcastData(): array
    {
        return [];
    }
}
