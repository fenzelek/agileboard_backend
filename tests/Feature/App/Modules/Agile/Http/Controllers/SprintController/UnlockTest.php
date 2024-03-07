<?php

namespace Tests\Feature\App\Modules\Agile\Http\Controllers\SprintController;

use App\Helpers\ErrorCode;
use App\Models\Db\Project;
use App\Models\Db\Sprint;
use App\Models\Other\RoleType;
use App\Modules\Agile\Events\UnlockSprintEvent;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Tests\BrowserKitTestCase;
use Tests\Helpers\ProjectHelper;

/**
 * Class PauseTest.
 */
class UnlockTest extends BrowserKitTestCase
{
    use DatabaseTransactions, ProjectHelper;

    /**
     * @test
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::unlock
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
            'locked' => true,
        ]);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->put('/projects/' . $project->id . '/sprints/' . $sprint->id .
            '/unlock?selected_company_id=' . $company->id, [])
            ->seeStatusCode(200);

        Event::assertDispatched(UnlockSprintEvent::class, function ($e) use ($project, $sprint) {
            if (
                $e->project->id == $project->id &&
                $e->sprint->id == $sprint->id
            ) {
                return true;
            }
        });

        $response_sprint = $this->decodeResponseJson()['data'];

        $this->assertSame('test', $response_sprint['name']);
        $this->assertSame($project->id, $response_sprint['project_id']);
        $this->assertSame(false, $response_sprint['locked']);
        $this->assertSame($now->toDateTimeString(), $response_sprint['updated_at']);
    }

    /**
     * @test
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::unlock
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
            'locked' => true,
        ]);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->put('/projects/' . $project->id . '/sprints/' . $sprint->id .
            '/unlock?selected_company_id=' . $company->id, [])
            ->seeStatusCode(200);

        $sprint = $sprint->fresh();

        $this->assertSame('test', $sprint->name);
        $this->assertSame($project->id, $sprint->project_id);
        $this->assertEquals(false, $sprint->locked);
        $this->assertSame($now->toDateTimeString(), $sprint->updated_at->toDateTimeString());
    }

    /**
     * @test
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::unlock
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

        $this->put('/projects/' . $project->id . '/sprints/' . $sprint->id .
            '/unlock?selected_company_id=' . $company->id, []);
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /**
     * @test
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::unlock
     */
    public function error_sprint_not_exist()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);

        $this->put('/projects/' . $project->id . '/sprints/0/unlock?selected_company_id=' .
            $company->id, []);
        $this->verifyErrorResponse(404, ErrorCode::RESOURCE_NOT_FOUND);
    }
}
