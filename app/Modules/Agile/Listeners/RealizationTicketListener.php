<?php

namespace App\Modules\Agile\Listeners;

use App\Events\EventInterface;
use App\Helpers\EventTypes;
use App\Models\Db\TicketRealization;
use Carbon\Carbon;

class RealizationTicketListener
{
    private $realization_ticket;

    /**
     * RealizationTicketListener constructor.
     * @param $ticket_realization
     */
    public function __construct(TicketRealization $ticket_realization)
    {
        $this->realization_ticket = $ticket_realization;
    }

    /**
     * Handle the event.
     *
     * @param  EventInterface $event
     *
     * @return void
     */
    public function handle(EventInterface $event)
    {
        //is enabled
        if ($event->getProject()->status_for_calendar_id === null) {
            return;
        }

        //end
        $current = $this->realization_ticket
            ->where('ticket_id', $event->ticket->id)
            ->orderByDesc('id')
            ->first();

        if ($current && ! $current->end_at) {
            $current->update(['end_at' => Carbon::now()]);
        }

        //start
        if ($event->getType() == EventTypes::TICKET_DELETE
            || ! $event->ticket->assigned_id
            || $event->ticket->status_id != $event->getProject()->status_for_calendar_id) {
            return;
        }

        $this->realization_ticket->create([
            'ticket_id' => $event->ticket->id,
            'user_id' => $event->ticket->assigned_id,
            'start_at' => Carbon::now(),
        ]);
    }
}
