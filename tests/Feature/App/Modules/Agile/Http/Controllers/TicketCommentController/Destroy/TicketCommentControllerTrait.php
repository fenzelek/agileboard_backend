<?php
declare(strict_types=1);

namespace Tests\Feature\App\Modules\Agile\Http\Controllers\TicketCommentController\Destroy;

use App\Models\Db\Interaction;
use App\Models\Db\InteractionPing;
use App\Models\Db\Project;
use App\Models\Db\Ticket;
use App\Models\Db\TicketComment;

trait TicketCommentControllerTrait
{
    private function createTicket(int $project_id): Ticket
    {
        return  factory(Ticket::class)->create(['project_id' => $project_id]);
    }
    private function createProject(int $company_id): Project
    {
        $project = factory(Project::class)->create(['company_id' => $company_id]);
        $this->setProjectRole($project);
        return $project;
    }
    private function createInteractionPing(int $interaction_id): InteractionPing
    {
        return factory(InteractionPing::class)->create([
            'interaction_id' => $interaction_id
        ]);
    }

    private function createInteraction(): Interaction
    {
        return factory(Interaction::class)->create();
    }

    private function createTicketComment(int $ticket_id, int $user_id): TicketComment
    {
        return factory(TicketComment::class)->create([
            'ticket_id' => $ticket_id,
            'user_id' => $user_id,
        ]);
    }
}