<?php

namespace App\Modules\Agile\Events;

use App\Helpers\EventTypes;

class AssignedTicketEvent extends AbstractTicketEvent
{
    public function getMessage(): array
    {
        return $this->generateMessage([
            'first_name' => $this->ticket->assignedUser->first_name,
            'last_name' => $this->ticket->assignedUser->last_name,
        ]);
    }

    public function getRecipients()
    {
        return $this->addRecipient(collect([]), $this->ticket->assignedUser);
    }

    public function getType(): string
    {
        return EventTypes::TICKET_ASSIGNED;
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
