<?php

namespace Tests\Feature\App\Modules\Agile\Http\Controllers\SprintController;

use App\Helpers\ErrorCode;
use App\Models\Db\Project;
use App\Models\Db\Sprint;
use App\Models\Other\RoleType;
use App\Modules\Agile\Events\ChangePrioritySprintEvent;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Tests\BrowserKitTestCase;
use Tests\Helpers\ProjectHelper;

class ChangePriorityTest extends BrowserKitTestCase
{
    use DatabaseTransactions, ProjectHelper;

    /**
     * @test
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::changePriority
     */
    public function priority_success_response()
    {
        Event::fake();

        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);
        $sprint_1 = factory(Sprint::class)->create([
            'project_id' => $project->id,
            'priority' => 1,
            'status' => Sprint::CLOSED,
        ]);
        $sprint_2 = factory(Sprint::class)->create([
            'project_id' => $project->id,
            'priority' => 2,
            'status' => Sprint::ACTIVE,
        ]);
        $sprint_3 = factory(Sprint::class)->create([
            'project_id' => $project->id,
            'priority' => 3,
            'status' => Sprint::INACTIVE,
        ]);
        $sprint_4 = factory(Sprint::class)->create([
            'project_id' => $project->id,
            'priority' => 1,
            'status' => Sprint::INACTIVE,
        ]);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->put(
            '/projects/' . $project->id . '/sprints' .
            '/change-priority?selected_company_id=' . $company->id,
            ['sprints' => [$sprint_3->id, $sprint_4->id, $sprint_2->id]]
        )
            ->seeStatusCode(200);

        Event::assertDispatched(ChangePrioritySprintEvent::class, function ($e) use ($project) {
            if ($e->project->id == $project->id) {
                return true;
            }
        });
    }

    /**
     * @test
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::changePriority
     */
    public function priority_success_db()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);
        $sprint_1 = factory(Sprint::class)->create([
            'project_id' => $project->id,
            'priority' => 1,
            'status' => Sprint::CLOSED,
        ]);
        $sprint_2 = factory(Sprint::class)->create([
            'project_id' => $project->id,
            'priority' => 2,
            'status' => Sprint::ACTIVE,
        ]);
        $sprint_3 = factory(Sprint::class)->create([
            'project_id' => $project->id,
            'priority' => 3,
            'status' => Sprint::INACTIVE,
        ]);
        $sprint_4 = factory(Sprint::class)->create([
            'project_id' => $project->id,
            'priority' => 4,
            'status' => Sprint::CLOSED,
        ]);
        $sprint_5 = factory(Sprint::class)->create([
            'project_id' => $project->id,
            'priority' => 5,
            'status' => Sprint::INACTIVE,
        ]);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->put(
            '/projects/' . $project->id . '/sprints' .
            '/change-priority?selected_company_id=' . $company->id,
            ['sprints' => [$sprint_3->id, $sprint_5->id, $sprint_2->id]]
        )
            ->seeStatusCode(200);

        $sprint_1 = $sprint_1->fresh();
        $this->assertSame(1, $sprint_1->priority);
        $sprint_2 = $sprint_2->fresh();
        $this->assertSame(7, $sprint_2->priority);
        $sprint_3 = $sprint_3->fresh();
        $this->assertSame(5, $sprint_3->priority);
        $sprint_4 = $sprint_4->fresh();
        $this->assertSame(4, $sprint_4->priority);
        $sprint_5 = $sprint_5->fresh();
        $this->assertSame(6, $sprint_5->priority);
    }

    /**
     * @test
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::changePriority
     */
    public function priority_error_has_not_permission()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::DEVELOPER);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $project_2 = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);
        $sprint = factory(Sprint::class)->create(['project_id' => $project_2->id]);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->put('/projects/' . $project_2->id . '/sprints' .
            '/change-priority?selected_company_id=' . $company->id, ['sprints' => [$sprint->id]]);
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /**
     * @test
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::changePriority
     */
    public function priority_error_invalid_current_status()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);
        $sprint_1 = factory(Sprint::class)->create([
            'project_id' => $project->id,
            'priority' => 1,
            'status' => Sprint::CLOSED,
        ]);
        $sprint_2 = factory(Sprint::class)->create([
            'project_id' => $project->id,
            'priority' => 2,
            'status' => Sprint::ACTIVE,
        ]);

        $this->put(
            '/projects/' . $project->id . '/sprints' .
            '/change-priority?selected_company_id=' . $company->id,
            ['sprints' => [$sprint_2->id, $sprint_1->id]]
        );
        $this->verifyValidationResponse(['sprints.1']);
    }
}
