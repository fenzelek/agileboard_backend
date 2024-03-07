<?php

namespace Tests\Feature\App\Modules\Project\Http\Controllers\ProjectController;

use App\Helpers\ErrorCode;
use App\Models\Db\Company;
use App\Models\Other\UserCompanyStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Other\RoleType;
use App\Models\Db\Project;
use Tests\Helpers\ProjectHelper;
use Tests\BrowserKitTestCase;

class BasicInfo extends BrowserKitTestCase
{
    use DatabaseTransactions, ProjectHelper;

    /** @test */
    public function it_returns_project_id_and_company_id_when_user_is_assigned_to_company()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::ADMIN);

        $project = factory(Project::class)->create([
            'company_id' => $company->id,
        ]);
        $this->setProjectRole($project, RoleType::CLIENT);

        auth()->login($this->user);

        $this->get('projects/' . $project->id . '/basic-info')->seeStatusCode(200);

        $this->assertEquals([
            'id' => $project->id,
            'company_id' => $company->id,
        ], $this->decodeResponseJson()['data']);
    }

    /** @test */
    public function it_returns_error_when_user_is_not_assigned_to_project()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::ADMIN);

        $project = factory(Project::class)->create([
            'company_id' => $company->id,
        ]);

        auth()->login($this->user);

        $this->get('projects/' . $project->id . '/basic-info');

        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function it_returns_error_when_user_is_not_assigned_to_project_company()
    {
        $this->createUser();
        $company = factory(Company::class)->create();

        $project = factory(Project::class)->create([
            'company_id' => $company->id,
        ]);
        $this->setProjectRole($project, RoleType::CLIENT);

        auth()->login($this->user);

        $this->get('projects/' . $project->id . '/basic-info');

        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function it_returns_error_when_user_is_assigned_to_company_with_non_approved_status()
    {
        $this->createUser();
        $company = $this->createCompanyWithRole(RoleType::ADMIN, UserCompanyStatus::REFUSED);

        $project = factory(Project::class)->create([
            'company_id' => $company->id,
        ]);
        $this->setProjectRole($project, RoleType::CLIENT);

        auth()->login($this->user);

        $this->get('projects/' . $project->id . '/basic-info');

        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }
}
