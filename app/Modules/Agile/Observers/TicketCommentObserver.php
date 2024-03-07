<?php

namespace App\Modules\Agile\Observers;

use App\Models\Db\TicketComment;
use App\Modules\Agile\Services\HistoryService;

class TicketCommentObserver
{
    /**
     * Listen to the TicketComment created event.
     *
     * @param  TicketComment $ticket_comment
     * @return void
     */
    public function created(TicketComment $ticket_comment)
    {
        HistoryService::add(
            $ticket_comment->ticket_id,
            $ticket_comment->id,
            HistoryService::TICKET_COMMENT,
            null,
            ['created_at' => $ticket_comment->created_at]
        );
    }

    /**
     * Listen to the TicketComment update event.
     *
     * @param  TicketComment $ticket_comment
     * @return void
     */
    public function updated(TicketComment $ticket_comment)
    {
        $data = [];
        foreach (array_keys($ticket_comment->getDirty()) as $name) {
            $data[$name] = $ticket_comment->getOriginal($name);
        }

        HistoryService::add(
            $ticket_comment->ticket_id,
            $ticket_comment->id,
            HistoryService::TICKET_COMMENT,
            $data,
            $ticket_comment->getDirty()
        );
    }

    /**
     * Listen to the TicketComment deleting event.
     *
     * @param  TicketComment $ticket_comment
     * @return void
     */
    public function deleted(TicketComment $ticket_comment)
    {
        HistoryService::add(
            $ticket_comment->ticket_id,
            $ticket_comment->id,
            HistoryService::TICKET_COMMENT,
            ['text' => $ticket_comment->text],
            null
        );
    }
}
