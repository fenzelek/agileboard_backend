<?php

namespace App\Modules\Agile\Events;

use App\Helpers\EventTypes;
use App\Models\Db\Project;
use App\Models\Db\Ticket;

class DeleteCommentEvent extends AbstractCommentEvent
{
    public function __construct(Project $project, Ticket $ticket)
    {
        $this->project = $project;
        $this->ticket = $ticket;
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
        return EventTypes::TICKET_COMMENT_DELETE;
    }
}
