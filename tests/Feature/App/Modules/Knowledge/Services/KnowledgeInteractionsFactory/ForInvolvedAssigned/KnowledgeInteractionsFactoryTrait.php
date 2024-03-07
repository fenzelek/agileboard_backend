<?php

declare(strict_types=1);

namespace Tests\Feature\App\Modules\Knowledge\Services\KnowledgeInteractionsFactory\ForInvolvedAssigned;

use App\Models\Db\Company;
use App\Models\Db\Involved;
use App\Models\Db\Knowledge\KnowledgePage;
use App\Models\Db\Project;

trait KnowledgeInteractionsFactoryTrait
{
    protected function createCompany(array $attributes = []): Company
    {
        return factory(Company::class)->create($attributes);
    }

    protected function createProject(array $attributes = []): Project
    {
        return factory(Project::class)->create($attributes);
    }

    protected function createKnowledgePage(array $attributes = []): KnowledgePage
    {
        return factory(KnowledgePage::class)->create($attributes);
    }

    private function createInvolved($params = []): Involved
    {
        return factory(Involved::class)->create($params);
    }
}
