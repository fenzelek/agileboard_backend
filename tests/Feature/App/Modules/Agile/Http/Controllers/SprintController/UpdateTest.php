<?php

namespace Tests\Feature\App\Modules\Agile\Http\Controllers\SprintController;

use App\Helpers\ErrorCode;
use App\Models\Db\Project;
use App\Models\Db\Sprint;
use App\Models\Other\RoleType;
use App\Modules\Agile\Events\UpdateSprintEvent;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Tests\BrowserKitTestCase;
use Tests\Helpers\ProjectHelper;

class UpdateTest extends BrowserKitTestCase
{
    use DatabaseTransactions, ProjectHelper;

    /** @test */
    public function update_it_returns_validation_error_without_data()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);

        $sprint = factory(Sprint::class)->create(['project_id' => $project->id, 'priority' => 1]);

        $this->put('/projects/' . $project->id . '/sprints/' . $sprint->id .
            '?selected_company_id=' . $company->id, []);

        $this->verifyValidationResponse(['name']);
    }

    /** @test */
    public function update_it_returns_validation_error_wrong_times()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);

        $sprint = factory(Sprint::class)->create(['project_id' => $project->id, 'priority' => 1]);

        $data = [
            'name' => ' update',
            'planned_activation' => '2017-11-20 10:10:10',
            'planned_closing' => '2017-10-20 10:10:10',
        ];

        $this->put('/projects/' . $project->id . '/sprints/' . $sprint->id .
            '?selected_company_id=' . $company->id, $data);

        $this->verifyValidationResponse(['planned_activation', 'planned_closing']);
    }

    /** @test */
    public function update_success_response()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        Event::fake();

        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);
        $sprint = factory(Sprint::class)->create([
            'project_id' => $project->id,
            'priority' => 1,
            'name' => 'test',
        ]);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->put('/projects/' . $project->id . '/sprints/' . $sprint->id .
            '?selected_company_id=' . $company->id, ['name' => ' update'])
            ->seeStatusCode(200);

        Event::assertDispatched(UpdateSprintEvent::class, function ($e) use ($project, $sprint) {
            if (
                $e->project->id == $project->id &&
                $e->sprint->id == $sprint->id) {
                return true;
            }
        });

        $response_sprint = $this->decodeResponseJson()['data'];

        $this->assertSame('update', $response_sprint['name']);
        $this->assertSame($project->id, $response_sprint['project_id']);
        $this->assertSame(Sprint::INACTIVE, $response_sprint['status']);
        $this->assertSame(1, $response_sprint['priority']);
        $this->assertSame($now->toDateTimeString(), $response_sprint['updated_at']);
        $this->assertSame(null, $response_sprint['planned_activation']);
        $this->assertSame(null, $response_sprint['planned_closing']);
        $this->assertSame(null, $response_sprint['activated_at']);
        $this->assertSame(null, $response_sprint['closed_at']);
    }

    /** @test */
    public function update_success_db()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);
        $sprint = factory(Sprint::class)->create([
            'project_id' => $project->id,
            'priority' => 4,
            'name' => 'test',
        ]);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $before_sprints_count = Sprint::count();

        $data = [
            'name' => ' update',
            'planned_activation' => '2017-10-20 10:10:10',
            'planned_closing' => '2017-11-20 10:10:10',
        ];

        $this->put('/projects/' . $project->id . '/sprints/' . $sprint->id .
            '?selected_company_id=' . $company->id, $data)
            ->seeStatusCode(200);

        $this->assertEquals($before_sprints_count, Sprint::count());
        $sprint = $sprint->fresh();

        $this->assertSame('update', $sprint->name);
        $this->assertSame($project->id, $sprint->project_id);
        $this->assertSame(Sprint::INACTIVE, $sprint->status);
        $this->assertSame(4, $sprint->priority);
        $this->assertSame($now->toDateTimeString(), $sprint->updated_at->toDateTimeString());
        $this->assertSame('2017-10-20 10:10:10', $sprint->planned_activation->toDateTimeString());
        $this->assertSame('2017-11-20 10:10:10', $sprint->planned_closing->toDateTimeString());
    }

    /** @test */
    public function update_error_sprint_not_exist()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);

        $this->put(
            '/projects/' . $project->id . '/sprints/0?selected_company_id=' . $company->id,
            ['name' => 'update']
        );
        $this->verifyErrorResponse(404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /** @test */
    public function update_error_has_not_permission()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $project_2 = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);
        $sprint = factory(Sprint::class)->create([
            'project_id' => $project_2->id,
            'priority' => 1,
            'name' => 'test',
        ]);

        $this->put('/projects/' . $project->id . '/sprints/' . $sprint->id .
            '?selected_company_id=' . $company->id, ['name' => 'update']);

        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }
}
