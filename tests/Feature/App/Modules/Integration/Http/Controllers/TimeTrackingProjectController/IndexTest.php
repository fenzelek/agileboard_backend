<?php

namespace Tests\Feature\App\Modules\Integration\Http\Controllers\TimeTrackingProjectController;

use App\Helpers\ErrorCode;
use App\Models\Db\Company;
use App\Models\Db\Integration\Integration;
use App\Models\Db\Integration\IntegrationProvider;
use App\Models\Db\Integration\TimeTracking\Project as TimeTrackingProject;
use App\Models\Db\Project;
use App\Models\Other\RoleType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Tests\BrowserKitTestCase;
use Tests\Helpers\ProjectHelper;
use Tests\Helpers\ResponseHelper;

class IndexTest extends BrowserKitTestCase
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
        ]);

        $this->upwork_integration = $this->company->integrations()->create([
            'integration_provider_id' => IntegrationProvider::findBySlug(IntegrationProvider::UPWORK)->id,
        ]);

        $this->projects = factory(Project::class, 2)->create(['company_id' => $this->company->id]);

        $this->tracking_projects = $this->createTimeTrackingProjects();
        $this->expected_response_projects = $this->getExpectedResponses();
    }

    /** @test */
    public function it_gets_list_of_all_company_projects_when_company_admin()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $this->get('/integrations/time_tracking/projects/' . '?selected_company_id=' .
            $this->company->id)
            ->seeStatusCode(200);

        $this->verifyResponseItems([0, 1, 2]);

        $this->seeJsonStructure([
            'meta' => [
                'pagination' => [
                    'total',
                    'count',
                    'per_page',
                    'current_page',
                    'total_pages',
                    'links',
                ],
            ],
        ]);
    }

    /** @test */
    public function it_gets_list_of_company_projects_filtered_by_project_id_when_company_admin()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $this->get('/integrations/time_tracking/projects/' . '?selected_company_id=' .
            $this->company->id . '&project_id=' . $this->projects[0]->id)
            ->seeStatusCode(200);

        $this->verifyResponseItems([1]);
    }

    /** @test */
    public function it_gets_list_of_company_projects_not_assigned_to_any_project_id_when_company_admin()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $this->get('/integrations/time_tracking/projects/' . '?selected_company_id=' .
            $this->company->id . '&project_id=empty')
            ->seeStatusCode(200);

        $this->verifyResponseItems([0]);
    }

    /** @test */
    public function it_gets_list_of_company_projects_filtered_by_external_project_name_when_company_admin()
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, RoleType::ADMIN);

        $this->get('/integrations/time_tracking/projects/' . '?selected_company_id=' .
            $this->company->id . '&external_project_name=very_specific_proj')
            ->seeStatusCode(200);

        $this->verifyResponseItems([0]);
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

    protected function verifyResponseItems(array $expected_project_ids)
    {
        $this->verifyDataResponse($expected_project_ids, $this->expected_response_projects);
    }

    protected function verifyNoPermissionForRole($role_slug)
    {
        $this->assignUsersToCompany(collect([$this->user]), $this->company, $role_slug);

        $this->get('/integrations/time_tracking/projects/' . '?selected_company_id=' .
            $this->company->id);

        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    protected function getExpectedResponses()
    {
        $responses = collect();
        $this->tracking_projects->each(function ($project) use ($responses) {
            $responses->push($this->getTimeTrackingResponse($project));
        });

        return $responses;
    }

    protected function getTimeTrackingResponse(TimeTrackingProject $project)
    {
        $project = $project->fresh();
        $data = $project->attributesToArray();
        $data['project']['data'] = $project->project ? $project->project->toArray() : null;

        return $data;
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
