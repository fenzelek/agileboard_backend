<?php

namespace App\Modules\Agile\Services;

use App\Models\Db\Project;
use App\Models\Db\Sprint;
use App\Models\Db\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketChangePriorityService
{
    /**
     * Change priority.
     *
     * @param Request $request
     * @param Project $project
     * @param Ticket $ticket
     *
     * @return Ticket
     */
    public function run(Request $request, Project $project, Ticket $ticket)
    {
        //new priority
        if ($request->input('before_ticket_id')) {
            $before_ticket = $ticket::findOrFail($request->input('before_ticket_id'));
            $new_priority = $before_ticket->priority;
            $has_tickets = true;
        } else {
            //search before priority
            if ($request->has('sprint_id')) {
                //first in sprint
                $tickets_query = $project->tickets()
                    ->where('sprint_id', $request->input('sprint_id'));
            } else {
                //first in agile
                $tickets_query = $project->tickets()->whereHas('sprint', function ($q) {
                    $q->where('status', '!=', Sprint::CLOSED);
                });
            }

            $has_tickets = $tickets_query->min('priority');
            $new_priority = $has_tickets ? $has_tickets - 1 : $ticket->priority;
        }

        DB::transaction(function () use ($request, $project, $new_priority, $ticket, $has_tickets) {

            //if sprint has tickets
            if ($has_tickets) {
                if ($new_priority < $ticket->priority) {
                    $this->ticketsPriorityUp($project, ++$new_priority, $ticket->priority);
                } else {
                    $this->ticketsPriorityDown($project, $ticket->priority, $new_priority);
                }
            }

            $this->updateSelectedTicket($ticket, $new_priority, $request);
        });

        return $ticket;
    }

    /**
     *  Change priority up in selected tickets.
     *
     * @param Project $project
     * @param int $from
     * @param int $to
     */
    private function ticketsPriorityUp(Project $project, $from, $to)
    {
        $project->tickets()->where('priority', '>=', $from)
            ->where('priority', '<', $to)
            ->increment('priority');
    }

    /**
     * Change priority down in selected tickets.
     *
     * @param Project $project
     * @param int $from
     * @param int $to
     */
    private function ticketsPriorityDown(Project $project, $from, $to)
    {
        $project->tickets()->where('priority', '>', $from)
            ->where('priority', '<=', $to)
            ->decrement('priority');
    }

    /**
     * Update current selected ticket.
     *
     * @param Ticket $ticket
     * @param int $new_priority
     * @param Request $request
     */
    private function updateSelectedTicket(Ticket $ticket, $new_priority, Request $request)
    {
        $ticket->update([
            'priority' => $new_priority,
            'sprint_id' => $request->input('sprint_id', $ticket->sprint_id),
            'status_id' => $request->input('status_id', $ticket->status_id),
        ]);
    }
}
