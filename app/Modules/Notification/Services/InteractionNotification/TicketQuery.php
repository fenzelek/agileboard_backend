<?php

namespace App\Modules\Notification\Services\InteractionNotification;

use App\Models\Db\Ticket;
use App\Models\Db\TicketComment;

class TicketQuery
{
    private Ticket $ticket;
    private TicketComment $ticket_comment;

    public function __construct(Ticket $ticket, TicketComment $ticket_comment)
    {
        $this->ticket = $ticket;
        $this->ticket_comment = $ticket_comment;
    }

    public function ticketExists(int $ticket_id): bool
    {
        return $this->ticket->newQuery()->where('id', $ticket_id)->exists();
    }

    public function getTicketWithTrashed(int $ticket_id): ?Ticket
    {
        /** @var ?Ticket */
        return $this->ticket->newQuery()->withTrashed()->find($ticket_id);
    }

    public function commentExists(int $ticket_comment_id): bool
    {
        return $this->ticket_comment->newQuery()->where('id', $ticket_comment_id)->exists();
    }

    public function getComment(int $ticket_comment_id): ?TicketComment
    {
        /** @var ?TicketComment */
        return $this->ticket_comment->newQuery()->find($ticket_comment_id);
    }
}
