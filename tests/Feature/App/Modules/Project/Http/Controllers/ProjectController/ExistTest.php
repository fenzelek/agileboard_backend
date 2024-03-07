<?php

namespace Tests\Feature\App\Modules\Project\Http\Controllers\ProjectController;

use App\Models\Db\Project;
use App\Models\Db\Role;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Other\RoleType;
use App\Helpers\ErrorCode;
use Carbon\Carbon;
use Tests\Helpers\CreateProjects;
use Tests\BrowserKitTestCase;

class ExistTest extends BrowserKitTestCase
{
    use DatabaseTransactions, CreateProjects;

    protected $company;
    protected $now;
    protected $project;

    public function setUp():void
    {
        parent::setUp();
        $this->now = Carbon::parse('2017-01-02 08:15:12');
        Carbon::setTestNow($this->now);

        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $this->company = $this->createCompanyWithRole(RoleType::ADMIN);

        $this->project = factory(Project::class)->create(['company_id' => $this->company->id]);
        $this->project->users()
            ->attach($this->user, ['role_id' => Role::findByName(RoleType::ADMIN)->id]);
    }

    /** @test */
    public function notExistShortName_error_exist()
    {
        $response = $this->get('/projects/exist?selected_company_id=' .
            $this->company->id . '&short_name=' . $this->project->short_name)->seeStatusCode(200);
    }

    /** @test */
    public function notExistShortName_success_not_exist()
    {
        $response = $this->get('/projects/exist?selected_company_id=' .
            $this->company->id . '&short_name=' . $this->project->short_name . 'T')->seeStatusCode(404);
    }

    /** @test */
    public function index_wrong_company_id_should_throw_401_exception()
    {
        $response = $this->get('/projects/exist?selected_company_id=' .
            ((int) $this->company->id + 1), [])
            ->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }
}
