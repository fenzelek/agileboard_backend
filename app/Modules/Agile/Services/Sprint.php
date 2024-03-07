<?php

namespace App\Modules\Agile\Services;

use App\Models\Db\Project;
use App\Models\Db\Sprint as SprintModel;
use App\Models\Db\Ticket;
use App\Modules\Project\Services\File;
use Illuminate\Database\Connection as DB;

class Sprint
{
    /** @var DB */
    private $db;

    /** @var File */
    private $file_service;

    /** @var TicketClone */
    private $ticket_clone_service;

    public function __construct(DB $db, File $file_service, TicketClone $ticket_clone_service)
    {
        $this->db = $db;
        $this->file_service = $file_service;
        $this->ticket_clone_service = $ticket_clone_service;
    }

    public function cloneSprint(SprintModel $base_sprint, $request): SprintModel
    {
        /** @var SprintModel $sprint */
        $sprint = $base_sprint->replicate();

        try {
            $this->db->beginTransaction();

            $sprint->name = $request->input('name');
            $sprint->status = $request->input('activated') === true
                ? SprintModel::ACTIVE
                : SprintModel::INACTIVE;
            $sprint->activated_at = $request->input('activated') === true ? now() : null;
            $sprint->push();

            $this->cloneTickets($base_sprint, $sprint);

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }

        return $sprint;
    }

    /**
     * @param SprintModel $base_sprint
     * @param SprintModel $sprint
     */
    private function cloneTickets(SprintModel $base_sprint, SprintModel $sprint)
    {
        $map_of_cloned_tickets = [];

        foreach ($base_sprint->tickets as $ticket) {
            $cloned_ticket = $ticket->replicate();
            $cloned_ticket->title = $this->createTicketTitle($base_sprint->project);
            $cloned_ticket->push();

            foreach ($ticket->comments as $comment) {
                $cloned_comment = $comment->replicate();
                $cloned_comment->push();
                $cloned_ticket->comments()->save($cloned_comment);
            }

            foreach ($ticket->stories as $story) {
                $cloned_ticket->stories()->attach($story);
            }

            $sprint->tickets()->save($cloned_ticket);
            $this->incrementNumberOfTickets($base_sprint->project);

            $this->file_service->cloneFilesInProject(
                $ticket,
                $cloned_ticket,
                $sprint->project,
                $sprint->project
            );

            $map_of_cloned_tickets[$ticket->id] = $cloned_ticket;
        }

        $this->ticket_clone_service->cloneRelatedTickets($base_sprint->tickets, $map_of_cloned_tickets);
    }

    /**
     * @param Project $project
     *
     * @return string
     */
    private function createTicketTitle(Project $project): string
    {
        /** @var Ticket $ticket */
        $ticket = $project->tickets()->latest('id')->first();
        $title_array = explode('-', $ticket->title);
        $title_array[1] = $project->created_tickets + 1;

        return implode('-', $title_array);
    }

    /**
     * @param Project $project
     */
    private function incrementNumberOfTickets(Project $project)
    {
        $project->created_tickets = $project->created_tickets + 1;
        $project->save();
    }
}
