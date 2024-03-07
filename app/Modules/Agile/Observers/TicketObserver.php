<?php

namespace App\Modules\Agile\Observers;

use Auth;
use App\Models\Db\Ticket;
use App\Modules\Agile\Events\AssignedTicketEvent;
use App\Modules\Agile\Events\MoveTicketEvent;
use App\Modules\Agile\Services\HistoryService;

class TicketObserver
{
    /**
     * Listen to the TicketComment created event.
     *
     * @param  Ticket $ticket
     * @return void
     */
    public function created(Ticket $ticket)
    {
        HistoryService::add(
            $ticket->id,
            $ticket->id,
            HistoryService::TICKET,
            null,
            ['created_at' => $ticket->created_at]
        );
    }

    /**
     * Listen to the TicketComment update event.
     *
     * @param  Ticket $ticket
     * @return void
     */
    public function updated(Ticket $ticket)
    {
        $data = [];
        foreach (array_keys($ticket->getDirty()) as $name) {
            $data[$name] = $ticket->getOriginal($name);
        }

        HistoryService::add(
            $ticket->id,
            $ticket->id,
            HistoryService::TICKET,
            $data,
            $ticket->getDirty()
        );

        if (isset($ticket->getDirty()['assigned_id']) && $ticket->getDirty()['assigned_id'] &&
            $data['assigned_id'] != $ticket->getDirty()['assigned_id']) {
            event(new AssignedTicketEvent($ticket->project, $ticket, Auth::user()));
        }

        if (isset($ticket->getDirty()['status_id']) && $ticket->getDirty()['status_id'] &&
            $data['status_id'] != $ticket->getDirty()['status_id']) {
            event(new MoveTicketEvent($ticket->project, $ticket, Auth::user()));
        }
    }

    /**
     * Listen to the TicketComment deleting event.
     *
     * @param  Ticket $ticket
     * @return void
     */
    public function deleted(Ticket $ticket)
    {
        HistoryService::add(
            $ticket->id,
            $ticket->id,
            HistoryService::TICKET,
            ['deleted_at' => null],
            $ticket
        );
    }
}
