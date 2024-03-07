<?php

namespace Tests\Feature\App\Modules\Project\Http\Controllers\ProjectController;

use App\Models\Db\Project;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Other\RoleType;
use App\Models\Db\User;
use App\Models\Db\Company;
use App\Helpers\ErrorCode;
use Carbon\Carbon;
use Tests\Helpers\CreateProjects;
use Tests\BrowserKitTestCase;

class IndexTest extends BrowserKitTestCase
{
    use DatabaseTransactions, CreateProjects;

    protected $company;
    protected $new_company;
    protected $now;
    protected $projects;
    protected $developer;

    public function setUp():void
    {
        parent::setUp();
        $this->now = Carbon::parse('2017-01-02 08:15:12');
        Carbon::setTestNow($this->now);

        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $this->company = $this->createCompanyWithRole(RoleType::ADMIN);
        $this->new_company = factory(Company::class)->create();

        $this->developer = factory(User::class)->create();
        $this->assignUsersToCompany($this->developer->get(), $this->company, RoleType::DEVELOPER);
    }

    /** @test */
    public function index_return_open_projects_for_admin()
    {
        $projects_user_should_see = $this->projectForUser('admin', 'opened');
        $this->assertCount(4, $projects_user_should_see);

        $response = $this->get(
            '/projects/?selected_company_id=' . $this->company->id .
                '&status=opened'
        )->seeStatusCode(200)
            ->seeJsonStructure($this->getStructure());
        $projects = collect($response->response->getData()->data);

        $this->verifyProjects($projects_user_should_see, $projects);
    }

    /** @test */
    public function index_return_all_projects_for_admin()
    {
        $projects_user_should_see = $this->projectForUser('admin', 'all');
        $this->assertCount(8, $projects_user_should_see);

        $response = $this->get(
            '/projects/?selected_company_id=' . $this->company->id .
                '&status=all'
        )->seeStatusCode(200)
            ->seeJsonStructure($this->getStructure());
        $projects = collect($response->response->getData()->data);

        $this->verifyProjects($projects_user_should_see, $projects);
    }

    /** @test */
    public function index_return_access_projects_for_admin()
    {
        $project = factory(Project::class)->create(['company_id' => $this->company->id]);
        $project->users()->attach($this->user);
        $project2 = factory(Project::class)->create([
            'company_id' => $this->company->id,
            'closed_at' => Carbon::now()->toDateTimeString(),
        ]);
        $project2->users()->attach($this->user);

        $response = $this->get(
            '/projects/?selected_company_id=' . $this->company->id .
            '&has_access=1'
        )->seeStatusCode(200)->decodeResponseJson()['data'];

        $this->assertSame(1, count($response));
        $this->assertSame($project->id, $response[0]['id']);
    }

    /** @test */
    public function index_return_projects_filtered_by_name_for_admin()
    {
        $created_projects = $this->projectForUser('admin', 'all')->values();
        $created_projects[0]->update(['name' => 'Test ABC DEF']);
        $created_projects[1]->update(['name' => 'Test GHI DEF']);
        $created_projects[2]->update(['name' => 'Test WDF DEF']);
        $created_projects[3]->update(['name' => 'Test abc DEF 2']);
        $created_projects[4]->update(['name' => 'Test DEFG2']);
        $created_projects[5]->update(['name' => 'Test DEFG 3']);
        $created_projects[6]->update(['name' => 'Test DEFG 4']);
        $created_projects[7]->update(['name' => 'abc']);

        $this->assertCount(8, $created_projects);

        $response = $this->get(
            '/projects/?selected_company_id=' . $this->company->id .
            '&status=all&search=abc'
        )->seeStatusCode(200)
            ->seeJsonStructure($this->getStructure());
        $projects = collect($response->response->getData()->data);

        $this->verifyProjects(collect([$created_projects[0], $created_projects[3],
            $created_projects[7], ]), $projects);
    }

    /** @test */
    public function index_return_closed_projects_for_admin()
    {
        $projects_user_should_see = $this->projectForUser('admin', 'closed');
        $this->assertCount(4, $projects_user_should_see);

        $response = $this->get(
            '/projects/?selected_company_id=' . $this->company->id .
                '&status=closed'
        )->seeStatusCode(200)
            ->seeJsonStructure($this->getStructure());
        $projects = collect($response->response->getData()->data);

        $this->verifyProjects($projects_user_should_see, $projects);
    }

    /** @test */
    public function index_return_all_projects_for_developer()
    {
        $this->be($this->developer);

        $projects_user_should_see = $this->projectForUser('developer');

        $this->assertCount(2, $projects_user_should_see);

        $response = $this->get('/projects/?selected_company_id=' . $this->company->id)
            ->seeStatusCode(200)
            ->seeJsonStructure($this->getStructure());
        $projects = collect($response->response->getData()->data);

        $this->verifyProjects($projects_user_should_see, $projects);
    }

    /** @test */
    public function index_developer_trying_get_all_projects_should_get_only_his()
    {
        $this->be($this->developer);

        $projects_user_should_see = $this->projectForUser('developer');
        $this->assertCount(2, $projects_user_should_see);

        $response = $this->get(
            '/projects/?selected_company_id=' . $this->company->id .
                '&status=all'
        )->seeStatusCode(200)
            ->seeJsonStructure($this->getStructure());
        $projects = collect($response->response->getData()->data);

        $this->verifyProjects($projects_user_should_see, $projects);
    }

    /** @test */
    public function index_developer_trying_get_closed_projects_should_get_only_his()
    {
        $this->be($this->developer);

        $projects_user_should_see = $this->projectForUser('developer');
        $this->assertCount(2, $projects_user_should_see);

        $response = $this->get(
            '/projects/?selected_company_id=' . $this->company->id .
                '&status=closed'
        )->seeStatusCode(200)
            ->seeJsonStructure($this->getStructure());
        $projects = collect($response->response->getData()->data);

        $this->verifyProjects($projects_user_should_see, $projects);
    }

    /** @test */
    public function index_wrong_company_id_should_throw_401_exception()
    {
        $response = $this->get('/projects/?selected_company_id=' .
            ((int) $this->company->id + 1))
            ->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }
}
