<?php
declare(strict_types=1);

namespace Tests\Unit\App\Modules\Involved\Services\InvolvedService\DeleteInvolved;

use App\Interfaces\Involved\IInvolvedRequest;
use App\Models\Db\Company;
use App\Models\Db\Involved;
use App\Models\Db\Knowledge\KnowledgePage;
use App\Models\Db\Project;
use App\Models\Db\Ticket;

trait InvolvedServiceTrait
{
    private function createTicket(int $project_id): Ticket
    {
        return factory(Ticket::class)->create([
            'project_id' => $project_id,
        ]);
    }
    private function createKnowledgePage(int $project_id): KnowledgePage
    {
        return factory(KnowledgePage::class)->create([
            'project_id' => $project_id,
        ]);
    }

    private function createCompany(): Company
    {
        return factory(Company::class)->create();
    }

    private function createProject(): Project
    {
        return factory(Project::class)->create();
    }

    private function createInvolved($params = []): Involved
    {
        return factory(Involved::class)->create($params);
    }
}