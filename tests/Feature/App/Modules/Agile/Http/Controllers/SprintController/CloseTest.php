<?php

namespace Tests\Feature\App\Modules\Agile\Http\Controllers\SprintController;

use App\Helpers\ErrorCode;
use App\Models\Db\History;
use App\Models\Db\HistoryField;
use App\Models\Db\Project;
use App\Models\Db\Sprint;
use App\Models\Db\Status;
use App\Models\Db\Ticket;
use App\Models\Other\RoleType;
use App\Modules\Agile\Events\CloseSprintEvent;
use App\Modules\Agile\Services\HistoryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Tests\BrowserKitTestCase;
use Tests\Helpers\ProjectHelper;

class CloseTest extends BrowserKitTestCase
{
    use DatabaseTransactions, ProjectHelper;

    /**
     * @test
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::close
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
            'status' => Sprint::ACTIVE,
        ]);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->put('/projects/' . $project->id . '/sprints/' . $sprint->id .
            '/close?selected_company_id=' . $company->id, [])
            ->seeStatusCode(200);

        Event::assertDispatched(CloseSprintEvent::class, function ($e) use ($project, $sprint) {
            if (
                $e->project->id == $project->id &&
                $e->sprint->id == $sprint->id &&
                $e->destination_sprint_id == null
            ) {
                return true;
            }
        });

        $response_sprint = $this->decodeResponseJson()['data'];

        $this->assertSame('test', $response_sprint['name']);
        $this->assertSame($project->id, $response_sprint['project_id']);
        $this->assertSame(Sprint::CLOSED, $response_sprint['status']);
        $this->assertSame($now->toDateTimeString(), $response_sprint['updated_at']);
    }

    /**
     * @test
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::close
     */
    public function success_db_move_tickets_to_sprint()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);
        $sprint_1 = factory(Sprint::class)->create([
            'project_id' => $project->id,
            'priority' => 1,
            'name' => 'test1',
            'status' => Sprint::ACTIVE,
        ]);
        $sprint_2 = factory(Sprint::class)->create([
            'project_id' => $project->id,
            'priority' => 2,
            'name' => 'test2',
            'status' => Sprint::ACTIVE,
        ]);
        $status_1 = factory(Status::class)->create(['project_id' => $project->id, 'priority' => 1]);
        $status_2 = factory(Status::class)->create(['project_id' => $project->id, 'priority' => 2]);

        $ticket_1 = factory(Ticket::class)->create([
            'project_id' => $project->id,
            'status_id' => $status_1->id,
            'sprint_id' => $sprint_1->id,
        ]);
        $ticket_2 = factory(Ticket::class)->create([
            'project_id' => $project->id,
            'status_id' => $status_2->id,
            'sprint_id' => $sprint_1->id,
        ]);
        $ticket_3 = factory(Ticket::class)->create([
            'project_id' => $project->id,
            'status_id' => $status_2->id,
            'sprint_id' => $sprint_2->id,
        ]);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $before_history = History::count();

        $this->put('/projects/' . $project->id . '/sprints/' . $sprint_1->id .
            '/close?selected_company_id=' . $company->id, ['sprint_id' => $sprint_2->id])
            ->seeStatusCode(200);

        $sprint_1 = $sprint_1->fresh();

        $this->assertSame('test1', $sprint_1->name);
        $this->assertSame($project->id, $sprint_1->project_id);
        $this->assertSame(Sprint::CLOSED, $sprint_1->status);
        $this->assertSame($now->toDateTimeString(), $sprint_1->updated_at->toDateTimeString());
        $this->assertSame($now->toDateTimeString(), $sprint_1->closed_at->toDateTimeString());

        $ticket_1 = $ticket_1->fresh();
        $this->assertSame($sprint_2->id, $ticket_1->sprint_id);
        $ticket_2 = $ticket_2->fresh();
        $this->assertSame($sprint_1->id, $ticket_2->sprint_id);
        $ticket_3 = $ticket_3->fresh();
        $this->assertSame($sprint_2->id, $ticket_3->sprint_id);

        //history
        $this->assertEquals($before_history + 1, History::count());
        $history = History::latest('id')->first();
        $this->assertSame($this->user->id, $history->user_id);
        $this->assertSame($ticket_1->id, $history->resource_id);
        $this->assertSame($ticket_1->id, $history->object_id);
        $this->assertSame(
            HistoryField::getId(HistoryService::TICKET, 'sprint_id'),
            $history->field_id
        );
        $this->assertEquals($sprint_1->id, $history->value_before);
        $this->assertSame($sprint_1->name, $history->label_before);
        $this->assertEquals($sprint_2->id, $history->value_after);
        $this->assertSame($sprint_2->name, $history->label_after);
        $this->assertSame($now->toDateTimeString(), $history->created_at->toDateTimeString());
    }

    /**
     * @test
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::close
     */
    public function success_db_move_tickets_to_backlog()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);
        $sprint_1 = factory(Sprint::class)->create([
            'project_id' => $project->id,
            'priority' => 1,
            'name' => 'test',
            'status' => Sprint::ACTIVE,
        ]);
        $sprint_2 = factory(Sprint::class)->create([
            'project_id' => $project->id,
            'priority' => 2,
            'name' => 'test',
            'status' => Sprint::ACTIVE,
        ]);
        $status_1 = factory(Status::class)->create(['project_id' => $project->id, 'priority' => 1]);
        $status_2 = factory(Status::class)->create(['project_id' => $project->id, 'priority' => 2]);

        $ticket_1 = factory(Ticket::class)->create([
            'project_id' => $project->id,
            'status_id' => $status_1->id,
            'sprint_id' => $sprint_1->id,
        ]);
        $ticket_2 = factory(Ticket::class)->create([
            'project_id' => $project->id,
            'status_id' => $status_2->id,
            'sprint_id' => $sprint_1->id,
        ]);
        $ticket_3 = factory(Ticket::class)->create([
            'project_id' => $project->id,
            'status_id' => $status_2->id,
            'sprint_id' => $sprint_2->id,
        ]);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $before_history = History::count();

        $this->put('/projects/' . $project->id . '/sprints/' . $sprint_1->id .
            '/close?selected_company_id=' . $company->id, ['sprint_id' => null])
            ->seeStatusCode(200);

        $sprint_1 = $sprint_1->fresh();

        $this->assertSame('test', $sprint_1->name);
        $this->assertSame($project->id, $sprint_1->project_id);
        $this->assertSame(Sprint::CLOSED, $sprint_1->status);
        $this->assertSame($now->toDateTimeString(), $sprint_1->updated_at->toDateTimeString());
        $this->assertSame($now->toDateTimeString(), $sprint_1->closed_at->toDateTimeString());

        $ticket_1 = $ticket_1->fresh();
        $this->assertSame(0, $ticket_1->sprint_id);
        $ticket_2 = $ticket_2->fresh();
        $this->assertSame($sprint_1->id, $ticket_2->sprint_id);
        $ticket_3 = $ticket_3->fresh();
        $this->assertSame($sprint_2->id, $ticket_3->sprint_id);

        //history
        $this->assertEquals($before_history + 1, History::count());
        $history = History::latest('id')->first();
        $this->assertSame($this->user->id, $history->user_id);
        $this->assertSame($ticket_1->id, $history->resource_id);
        $this->assertSame($ticket_1->id, $history->object_id);
        $this->assertSame(
            HistoryField::getId(HistoryService::TICKET, 'sprint_id'),
            $history->field_id
        );
        $this->assertEquals($sprint_1->id, $history->value_before);
        $this->assertSame($sprint_1->name, $history->label_before);
        $this->assertEquals(0, $history->value_after);
        $this->assertSame('Backlog', $history->label_after);
        $this->assertSame($now->toDateTimeString(), $history->created_at->toDateTimeString());
    }

    /**
     * @test
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::close
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
            '/close?selected_company_id=' . $company->id, []);
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /**
     * @test
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::close
     */
    public function error_sprint_not_exist()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);

        $this->put('/projects/' . $project->id . '/sprints/0/close?selected_company_id=' .
            $company->id, []);
        $this->verifyErrorResponse(404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /**
     * @test
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::close
     */
    public function error_invalid_current_status()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);
        $sprint = factory(Sprint::class)->create([
            'project_id' => $project->id,
            'status' => Sprint::CLOSED,
        ]);

        $this->put('/projects/' . $project->id . '/sprints/' . $sprint->id .
            '/close?selected_company_id=' . $company->id, []);
        $this->verifyErrorResponse(409, ErrorCode::SPRINT_INVALID_STATUS);
    }
}
