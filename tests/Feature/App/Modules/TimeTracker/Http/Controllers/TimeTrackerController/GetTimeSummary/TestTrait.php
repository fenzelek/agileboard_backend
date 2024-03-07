<?php

namespace Tests\Feature\App\Modules\TimeTracker\Http\Controllers\TimeTrackerController\GetTimeSummary;

use App\Models\Db\Package;
use App\Models\Db\Project;
use App\Models\Db\Ticket;
use App\Models\Other\RoleType;

trait TestTrait
{
    protected function prepareProject($company)
    {
        $project = factory(Project::class)->create([
            'company_id' => $company->id,
        ]);
        $project->users()->attach($this->user->id);

        return $project;
    }

    protected function prepareTicket($project)
    {
        return factory(Ticket::class)->create([
            'project_id' => $project->id,
        ]);
    }

    protected function prepareCompany()
    {
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::CEP_FREE);
        $company->roles()->attach([4]);

        return $company;
    }
}
