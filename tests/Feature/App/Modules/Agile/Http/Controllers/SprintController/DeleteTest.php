<?php

namespace Tests\Feature\App\Modules\Agile\Http\Controllers\SprintController;

use App\Helpers\ErrorCode;
use App\Models\Db\Project;
use App\Models\Db\Sprint;
use App\Models\Db\Ticket;
use App\Models\Other\RoleType;
use App\Modules\Agile\Events\DeleteSprintEvent;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Tests\BrowserKitTestCase;
use Tests\Helpers\ProjectHelper;

class DeleteTest extends BrowserKitTestCase
{
    use DatabaseTransactions, ProjectHelper;

    /**
     * @test
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::destroy
     */
    public function success_response()
    {
        Event::fake();

        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);
        $sprint = factory(Sprint::class)->create([
            'project_id' => $project->id,
            'priority' => 1,
            'name' => 'test',
            'status' => Sprint::INACTIVE,
        ]);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->delete('/projects/' . $project->id . '/sprints/' . $sprint->id .
            '?selected_company_id=' . $company->id, [])
            ->seeStatusCode(204);

        Event::assertDispatched(DeleteSprintEvent::class, function ($e) use ($project, $sprint) {
            if (
                $e->project->id == $project->id &&
                $e->sprint == $sprint->id
            ) {
                return true;
            }
        });
    }

    /**
     * @test
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::destroy
     */
    public function success_db()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);
        $sprint = factory(Sprint::class)->create([
            'project_id' => $project->id,
            'priority' => 1,
            'name' => 'test',
            'status' => Sprint::INACTIVE,
        ]);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $before_sprints = Sprint::count();

        $this->delete('/projects/' . $project->id . '/sprints/' . $sprint->id .
            '?selected_company_id=' . $company->id, [])
            ->seeStatusCode(204);

        $this->assertSame(null, $sprint->fresh());
        $this->assertEquals($before_sprints - 1, Sprint::count());
    }

    /**
     * @test
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::destroy
     */
    public function error_has_not_permission()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $project_2 = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);
        $sprint = factory(Sprint::class)->create(['project_id' => $project_2->id]);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->delete('/projects/' . $project->id . '/sprints/' . $sprint->id .
            '?selected_company_id=' . $company->id, []);
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /**
     * @test
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::destroy
     */
    public function error_sprint_not_exist()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);

        $this->delete('/projects/' . $project->id . '/sprints/0?selected_company_id=' .
            $company->id, []);
        $this->verifyErrorResponse(404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /**
     * @test
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::destroy
     */
    public function error_sprint_not_empty()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);
        $sprint = factory(Sprint::class)->create([
            'project_id' => $project->id,
            'status' => Sprint::ACTIVE,
        ]);
        factory(Ticket::class)
            ->create(['project_id' => $project->id, 'status_id' => 1, 'sprint_id' => $sprint->id]);

        $this->delete('/projects/' . $project->id . '/sprints/' . $sprint->id .
            '?selected_company_id=' . $company->id, []);
        $this->verifyErrorResponse(409, ErrorCode::SPRINT_NOT_EMPTY);
    }
}
