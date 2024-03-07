<?php

declare(strict_types=1);

namespace Tests\Feature\App\Modules\Agile\Services\TicketInteractionFactory\ForInvolvedAssigned;

use App\Models\Db\Company;
use App\Models\Db\Involved;
use App\Models\Db\Project;
use App\Models\Db\Ticket;

trait TicketInteractionFactoryTrait
{
    protected function createCompany(array $attributes = []): Company
    {
        return factory(Company::class)->create($attributes);
    }

    protected function createProject(array $attributes = []): Project
    {
        return factory(Project::class)->create($attributes);
    }

    protected function createTicket(array $attributes = []): Ticket
    {
        return factory(Ticket::class)->create($attributes);
    }

    private function createInvolved($params = []): Involved
    {
        return factory(Involved::class)->create($params);
    }
}
