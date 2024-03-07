<?php

declare(strict_types=1);

namespace App\Modules\Agile\Services;

use App\Exports\SprintExport;
use App\Models\Db\Project;
use App\Models\Db\Sprint;
use App\Models\Db\Ticket;
use App\Modules\Agile\Models\TicketExportDto;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class SprintExportService
{
    public function makeExport(Project $project, Sprint $sprint): SprintExport
    {
        return new SprintExport($this->getSprintTickets($sprint), $sprint->name??'');
    }

    private function getSprintTickets(Sprint $sprint): Collection
    {
        return $sprint->tickets()
            ->with([
                'assignedUser' => function (BelongsTo $builder) {
                    return $builder->select('id', 'first_name', 'last_name');
                },
            ])
            ->select('id', 'title', 'name', 'estimate_time', 'assigned_id')
            ->get()
            ->map(function (Ticket $ticket) {
                return $this->toTicketDto($ticket);
            });
    }

    private function toTicketDto(Ticket $ticket): TicketExportDto
    {
        $user = $ticket->assignedUser;
        $tracking_summary = $ticket->timeTrackingGeneralSummary()->first();
        $total_tracked_time = $tracking_summary ? $tracking_summary->tracked_sum : 0;

        return new TicketExportDto(
            $ticket->id,
            $ticket->name??'',
            $ticket->title??'',
            $user ? $user->first_name : '',
            $user ? $user->last_name : '',
            (int) $ticket->estimate_time,
            (int) $total_tracked_time
        );
    }
}
