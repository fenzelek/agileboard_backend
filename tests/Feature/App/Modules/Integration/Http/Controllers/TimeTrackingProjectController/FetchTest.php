<?php

namespace Tests\Feature\App\Modules\Integration\Http\Controllers\TimeTrackingProjectController;

use App\Helpers\ErrorCode;
use App\Models\Db\Company;
use App\Models\Db\Integration\Integration;
use App\Models\Db\Integration\IntegrationProvider;
use App\Models\Db\Integration\TimeTracking\Project as TimeTrackingProject;
use App\Models\Db\Integration\TimeTracking\Project as TimeTrackingDatabaseProject;
use App\Models\Db\Project;
use App\Models\Other\RoleType;
use App\Modules\Integration\Services\Factory;
use App\Modules\Integration\Services\TimeTracking\Hubstaff;
use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use stdClass;
use Tests\BrowserKitTestCase;
use Tests\Helpers\ProjectHelper;
use Tests\Helpers\ResponseHelper;
use Mockery as m;

class FetchTest extends BrowserKitTestCase
{
    use DatabaseTransactions, ResponseHelper, ProjectHelper;

    /**
     * @var Company
     */
    protected $company;

    /**
     * @var Integration
     */
    protected $hubstaff_integration;

    /**
     * @var Integration
     */
    protected $upwork_integration;

    /**
     * @var Collection
     */
    protected $projects;

    /**
     * @var Collection
     */
    protected $tracking_projects;

    /**
     * @var Collection
     */
    protected $expected_response_projects;

    /**
     * @inheritdoc
     */
    public function setUp():void
    {
        parent::setUp();
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $this->company = factory(Company::class)->create();

        $this->hubstaff_integration = $this->company->integrations()->create([
            'integration_provider_id' => IntegrationProvider::findBySlug(IntegrationProvider::HUBSTAFF)->id,
            'settings' => [
                'app_token' => 'sample token',
                'auth_token' => 'sample token',
            ],
        ]);

        $this->upwork_integration = $this->company->integrations()->create([
            'integration_provider_id' => IntegrationProvider::findBySlug(IntegrationProvider::UPWORK)->id,
        ]);

        $this->projects = factory(Project::class, 2)->create(['company_id' => $this->company->id]);

        $this->tracking_projects = $this->createTimeTrackingProjects();
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_fetches_projects_when_company_admin()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $now = Carbon::now()->addDays(10);
        Carbon::setTestNow($now);

        $hubstaff_projects = [
            // this should be modified
            [
                'id' => $this->tracking_projects[0]->external_project_id,
                'name' => 'MODIFIED NAME',
                'last_activity' => '2017-08-13T11:10:00Z',
                'status' => 'Active',
                'description' => null,
            ],
            // this should be created
            [
                'id' => 'HUBSTAFF_NEW_PROJECT',
                'name' => 'NEW PROJECT ',
                'last_activity' => '2017-08-14T11:10:00Z',
                'status' => 'Inactive',
                'description' => null,
            ],
        ];

        $this->setHubstaffClientMock($hubstaff_projects);

        $this->put('/integrations/time_tracking/projects/fetch' . '?selected_company_id=' .
            $this->company->id, ['integration_id' => $this->hubstaff_integration->id])
            ->seeStatusCode(200);

        $this->assertSame([], $this->decodeResponseJson()['data']);

        // one was modified, one should be created
        $this->assertSame(count($this->tracking_projects) + 1, TimeTrackingProject::count());

        // first verify all existing projects
        foreach ($this->tracking_projects as $index => $project) {
            $db_project = TimeTrackingDatabaseProject::findOrFail($project->id);
            $attributes = $project->attributesToArray();

            if ($index == 0) {
                $attributes['external_project_name'] = $hubstaff_projects[0]['name'];
                $attributes['updated_at'] = $now->toDateTimeString();
            }

            $this->assertEquals($attributes, $db_project->attributesToArray());
        }

        // then verify new project
        $last_project = TimeTrackingProject::latest('id')->first();
        $this->assertEquals([
            'id' => $last_project->id,
            'integration_id' => $this->hubstaff_integration->id,
            'project_id' => null,
            'external_project_id' => $hubstaff_projects[1]['id'],
            'external_project_name' => $hubstaff_projects[1]['name'],
            'created_at' => $now->toDateTimeString(),
            'updated_at' => $now->toDateTimeString(),
        ], $last_project->attributesToArray());
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_fetches_projects_when_company_owner()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::OWNER);

        $now = Carbon::now()->addDays(10);
        Carbon::setTestNow($now);

        $this->setHubstaffClientMock([]);

        $this->put('/integrations/time_tracking/projects/fetch' . '?selected_company_id=' .
            $this->company->id, ['integration_id' => $this->hubstaff_integration->id])
            ->seeStatusCode(200);

        $this->assertSame([], $this->decodeResponseJson()['data']);

        // nothing was in Hubstaff, so everything should be exactly same
        $this->assertSame(count($this->tracking_projects), TimeTrackingProject::count());

        // first verify all existing projects
        foreach ($this->tracking_projects as $index => $project) {
            $db_project = TimeTrackingDatabaseProject::findOrFail($project->id);
            $this->assertEquals($project->attributesToArray(), $db_project->attributesToArray());
        }
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_gets_500_error_when_exception_was_thrown()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $this->put('/integrations/time_tracking/projects/fetch' . '?selected_company_id=' .
            $this->company->id, ['integration_id' => $this->hubstaff_integration->id]);

        $this->verifyErrorResponse(500, ErrorCode::API_ERROR);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_gets_validation_error_when_other_company_integration_id_was_sent()
    {
        $other_integration = factory(Integration::class)->create(
            ['company_id' => factory(Company::class)->create()->id]
        );

        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $this->put('/integrations/time_tracking/projects/fetch' . '?selected_company_id=' .
            $this->company->id, ['integration_id' => $other_integration->id]);

        $this->verifyValidationResponse(['integration_id']);
    }

    /** @test */
    public function it_gets_no_permission_for_developer()
    {
        $this->verifyNoPermissionForRole(RoleType::DEVELOPER);
    }

    /** @test */
    public function it_gets_no_permission_for_client()
    {
        $this->verifyNoPermissionForRole(RoleType::CLIENT);
    }

    /** @test */
    public function it_gets_no_permission_for_dealer()
    {
        $this->verifyNoPermissionForRole(RoleType::DEALER);
    }

    protected function setHubstaffClientMock(array $projects, $throw_exception = false)
    {
        // set up mocks - we don't want to connect to hubstaff API (no testing API) - we want to
        // return mocked data and make sure everything will be as it should
        $response_mock = m::mock(stdClass::class);
        $response_mock->shouldReceive('getBody')->once()->withNoArgs()
            ->andReturn(json_encode([
                'projects' => $projects,
            ]));

        $client_mock = m::mock(stdClass::class);

        if ($throw_exception) {
            $client_mock->shouldReceive('request')->once()
                ->andThrow(Exception::class, 'Dummy exception');
        } else {
            $client_mock->shouldReceive('request')->once()->andReturn($response_mock);
        }

        $hubstaff_mock =
            m::mock(Hubstaff::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $hubstaff_mock->shouldReceive('httpClient')->once()->andReturn($client_mock);

        $factory_mock = m::mock('overload:' . Factory::class);
        $factory_mock->shouldReceive('make')->once()->andReturn($hubstaff_mock);
    }

    protected function verifyNoPermissionForRole($role_slug)
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, $role_slug);

        $this->put('/integrations/time_tracking/projects/fetch' . '?selected_company_id=' .
            $this->company->id, ['integration_id' => $this->hubstaff_integration->id]);

        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    protected function createTimeTrackingProjects()
    {
        $tracking_projects = collect();
        $tracking_projects->push(factory(TimeTrackingProject::class)->create([
            'integration_id' => $this->hubstaff_integration->id,
            'project_id' => null,
            'external_project_name' => 'test very_specific_project abc',
        ]));

        $tracking_projects->push(factory(TimeTrackingProject::class)->create([
            'integration_id' => $this->hubstaff_integration->id,
            'project_id' => $this->projects[0]->id,
        ]));

        $tracking_projects->push(factory(TimeTrackingProject::class)->create([
            'integration_id' => $this->upwork_integration->id,
            'project_id' => $this->projects[1]->id,
        ]));

        $company = factory(Company::class)->create();
        $other_company_hubstaff_integration = $company->integrations()->create([
            'integration_provider_id' => IntegrationProvider::findBySlug(IntegrationProvider::HUBSTAFF)->id,
        ]);

        $tracking_projects->push(factory(TimeTrackingProject::class)->create([
            'integration_id' => $other_company_hubstaff_integration->id,
            'project_id' => $this->projects[1]->id,
        ]));

        return $tracking_projects;
    }
}
