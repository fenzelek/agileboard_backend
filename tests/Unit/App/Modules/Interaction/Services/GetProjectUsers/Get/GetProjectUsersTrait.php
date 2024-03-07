<?php
declare(strict_types=1);

namespace Tests\Unit\App\Modules\Interaction\Services\GetProjectUsers\Get;

use App\Models\Db\Company;
use App\Models\Db\Project;
use App\Models\Other\RoleType;
use App\Modules\Interaction\Contracts\IInteractionDTO;
use Mockery as m;

trait GetProjectUsersTrait
{

    private function mockInteraction(int $project_id): IInteractionDTO
    {
        $interaction =  m::mock(IInteractionDTO::class);
        $interaction->allows('getProjectId')->times(1)->andReturns($project_id);
        return $interaction;
    }

    protected function createProject(Company $company, $role_type = RoleType::OWNER): Project
    {
        return factory(Project::class)->create([
            'id' => 9999,
            'company_id' => $company->id,
            'name' => 'Test project',
            'short_name' => 'tp',
        ]);
    }

    private function createCompany(string $name)
    {
        return factory(Company::class)->create([
            'name' => $name,
        ]);
    }
}