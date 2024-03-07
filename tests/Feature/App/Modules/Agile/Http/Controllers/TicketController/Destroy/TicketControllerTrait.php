<?php
declare(strict_types=1);

namespace Tests\Feature\App\Modules\Agile\Http\Controllers\TicketController\Destroy;

use App\Models\Db\Company;
use App\Models\Db\Interaction;
use App\Models\Db\InteractionPing;
use App\Models\Db\Involved;
use App\Models\Db\Project;
use App\Models\Db\Status;
use App\Models\Db\Ticket;

trait TicketControllerTrait
{
    /**
     * @param $project_id
     * @param $ticket_id
     * @param $company_id
     *
     * @return string
     */
    private function prepareUrl(int $project_id, int $ticket_id, int $company_id): string
    {
        return "/projects/{$project_id}/tickets/{$ticket_id}?selected_company_id={$company_id}";
    }

    /**
     * @param int $project_id
     * @param int $priority
     *
     * @return Ticket
     */
    protected function createTicket(int $project_id, int $priority): Ticket
    {
        return factory(Ticket::class)->create([
            'project_id' => $project_id,
            'priority' => $priority,
        ]);
    }

    private function createInteractionPing(int $interaction_id): InteractionPing
    {
        return factory(InteractionPing::class)->create([
            'interaction_id' => $interaction_id
        ]);
    }

    private function createInteraction($params = []): Interaction
    {
        return factory(Interaction::class)->create($params);
    }

    private function createNewProject(int $company_id): Project
    {
        $project = factory(Project::class)->create(['company_id' => $company_id]);
        $this->setProjectRole($project);
        $status = $this->createNewStatus($project->id, 1);
        $project->update(['status_for_calendar_id' => $status->id]);
        return $project;
    }

    private function createNewStatus(int $project_id, int $priority): Status
    {
        return factory(Status::class)->create([
            'project_id' => $project_id,
            'priority' => $priority,
        ]);
    }

    private function createCompany(): Company
    {
        return factory(Company::class)->create();
    }

    private function createInvolved($params = []): Involved
    {
        return factory(Involved::class)->create($params);
    }

    private function createNewTicket($params = []): Ticket
    {
        return factory(Ticket::class)->create($params);
    }
}