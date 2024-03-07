<?php

namespace App\Modules\Agile\Events;

use App\Helpers\EventTypes;

class UpdateCommentEvent extends AbstractCommentEvent
{
    public function getMessage(): array
    {
        return [];
    }

    public function getType(): string
    {
        return EventTypes::TICKET_COMMENT_UPDATE;
    }
}
