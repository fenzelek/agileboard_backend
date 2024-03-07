<?php

namespace App\Modules\Agile\Services;

use App\Models\Db\Ticket;
use Illuminate\Support\Collection;

class TicketClone
{
    /**
     * @param Collection $tickets
     * @param Ticket[] $map_of_cloned_tickets
     */
    public function cloneRelatedTickets(Collection $tickets, array $map_of_cloned_tickets)
    {
        foreach ($tickets as $ticket) {
            if (($sub_ticket_ids = $ticket->subTickets()->pluck('id'))->isEmpty()) {
                continue;
            }

            $this->cloneSubTickets($sub_ticket_ids, $map_of_cloned_tickets, $ticket);
        }
    }

    /**
     * @param $sub_ticket_ids
     * @param array $map_of_cloned_tickets
     * @param $ticket
     */
    private function cloneSubTickets($sub_ticket_ids, array $map_of_cloned_tickets, $ticket)
    {
        foreach ($sub_ticket_ids as $sub_ticket_id) {
            $cloned_ticket = $map_of_cloned_tickets[$ticket->id];
            $cloned_sub_ticket = $map_of_cloned_tickets[$sub_ticket_id] ?? null;

            if (null === $cloned_sub_ticket) {
                $cloned_ticket->subTickets()->attach($sub_ticket_id);
                continue;
            }

            $cloned_ticket->subTickets()->attach($cloned_sub_ticket);
        }
    }
}
