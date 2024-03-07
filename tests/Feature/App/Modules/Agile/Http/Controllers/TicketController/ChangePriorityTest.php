<?php

namespace Tests\Feature\App\Modules\Agile\Http\Controllers\TicketController;

use App\Models\Db\History;
use App\Models\Db\HistoryField;
use App\Models\Db\Project;
use App\Models\Db\Sprint;
use App\Models\Db\Status;
use App\Models\Db\Ticket;
use App\Models\Db\TicketRealization;
use App\Models\Other\RoleType;
use App\Modules\Agile\Events\ChangePriorityTicketEvent;
use App\Modules\Agile\Services\HistoryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Tests\Helpers\ProjectHelper;
use Tests\Helpers\ResponseHelper;
use Tests\BrowserKitTestCase;

class ChangePriorityTest extends BrowserKitTestCase
{
    use DatabaseTransactions, ProjectHelper, ResponseHelper, TestTrait;

    /**
     * @scenario Changing Of Ticket Priority
     *      @suit Changing Of Ticket Priority
     *      @case Return error when input data is incorrect
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::changePriority
     * @test
     */
    public function changePriority_validation_error()
    {
        $this->initEnv();
        $data = $this->prepareData();
        $url = $this->prepareUrl($this->project->id, $data['ticket'][0]->id, $this->company->id);

        $this->put($url, ['sprint_id' => 'sdsad', 'status_id' => 0]);

        $this->verifyValidationResponse(['sprint_id', 'status_id'], ['before_ticket_id']);
    }

    /**
     * @scenario Changing Of Ticket Priority
     *      @suit Changing Of Ticket Priority
     *      @case Return error when user hasn't permission
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::changePriority
     * @test
     */
    public function changePriority_no_permissions_error()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::DEVELOPER);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $ticket = factory(Ticket::class)->create([
            'project_id' => $project->id,
            'priority' => 1,
            'hidden' => 0,
        ]);
        $url = $this->prepareUrl($project->id, $ticket->id, $company->id);

        $this->put($url)->seeStatusCode(401);
    }

    /**
     * @scenario Changing Of Ticket Priority
     *      @suit Changing Of Ticket Priority
     *      @case Success adding to empty sprint
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::changePriority
     * @test
     */
    public function changePriority_success_add_to_empty_sprint()
    {
        $this->initEnv();
        $data = $this->prepareData();
        $url = $this->prepareUrl($this->project->id, $data['ticket'][3]->id, $this->company->id);

        $data['ticket'][3]->sprint_id = 0;
        $data['ticket'][3]->save();

        History::whereRaw('1 = 1')->delete();

        $this->put($url, ['before_ticket_id' => null, 'sprint_id' => $data['sprints'][2]->id])
            ->seeStatusCode(200)
            ->isJson();

        $ticket_0 = $data['ticket'][0]->fresh();
        $ticket_1 = $data['ticket'][1]->fresh();
        $ticket_2 = $data['ticket'][2]->fresh();
        $ticket_3 = $data['ticket'][3]->fresh();

        $this->assertSame($data['ticket'][0]->sprint_id, $ticket_0->sprint_id);
        $this->assertSame($data['ticket'][0]->status_id, $ticket_0->status_id);
        $this->assertSame($data['ticket'][0]->priority, $ticket_0->priority);

        $this->assertSame($data['ticket'][1]->sprint_id, $ticket_1->sprint_id);
        $this->assertSame($data['ticket'][1]->status_id, $ticket_1->status_id);
        $this->assertSame($data['ticket'][1]->priority, $ticket_1->priority);

        $this->assertSame($data['ticket'][2]->sprint_id, $ticket_2->sprint_id);
        $this->assertSame($data['ticket'][2]->status_id, $ticket_2->status_id);
        $this->assertSame($data['ticket'][2]->priority, $ticket_2->priority);

        $this->assertSame($data['sprints'][2]->id, $ticket_3->sprint_id);
        $this->assertSame($data['ticket'][3]->status_id, $ticket_3->status_id);
        $this->assertSame($data['ticket'][3]->priority, $ticket_3->priority);

        //history
        $this->same_history(
            'sprint_id',
            $data['ticket'][3]->id,
            0,
            'Backlog',
            $data['sprints'][2]->id,
            $data['sprints'][2]->name
        );
    }

    /**
     * @scenario Changing Of Ticket Priority
     *      @suit Changing Of Ticket Priority
     *      @case Success increasing priority to first position in sprint
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::changePriority
     * @test
     */
    public function changePriority_success_increasing_to_first_position_in_sprint()
    {
        $this->initEnv();
        $data = $this->prepareData();
        $url = $this->prepareUrl($this->project->id, $data['ticket'][2]->id, $this->company->id);

        $this->put($url, ['before_ticket_id' => null, 'sprint_id' => $data['ticket'][2]->sprint_id])
            ->seeStatusCode(200)
            ->isJson();

        $ticket_0 = $data['ticket'][0]->fresh();
        $ticket_1 = $data['ticket'][1]->fresh();
        $ticket_2 = $data['ticket'][2]->fresh();
        $ticket_3 = $data['ticket'][3]->fresh();

        $this->assertSame($data['ticket'][0]->sprint_id, $ticket_0->sprint_id);
        $this->assertSame($data['ticket'][0]->status_id, $ticket_0->status_id);
        $this->assertSame($data['ticket'][0]->priority, $ticket_0->priority);

        $this->assertSame($data['ticket'][1]->sprint_id, $ticket_1->sprint_id);
        $this->assertSame($data['ticket'][1]->status_id, $ticket_1->status_id);
        $this->assertSame($data['ticket'][2]->priority, $ticket_1->priority);

        $this->assertSame($data['ticket'][2]->sprint_id, $ticket_2->sprint_id);
        $this->assertSame($data['ticket'][2]->status_id, $ticket_2->status_id);
        $this->assertSame($data['ticket'][1]->priority, $ticket_2->priority);

        $this->assertSame($data['ticket'][3]->sprint_id, $ticket_3->sprint_id);
        $this->assertSame($data['ticket'][3]->status_id, $ticket_3->status_id);
        $this->assertSame($data['ticket'][3]->priority, $ticket_3->priority);

        //history
        $this->same_history(
            'priority',
            $data['ticket'][2]->id,
            $data['ticket'][2]->priority,
            null,
            $data['ticket'][1]->priority,
            null
        );
    }

    /**
     * @scenario Changing Of Ticket Priority
     *      @suit Changing Of Ticket Priority
     *      @case Success increasing to second position in sprint
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::changePriority
     * @test
     */
    public function changePriority_success_increasing_to_second_position_in_sprint()
    {
        $this->initEnv();
        $data = $this->prepareData();
        $url = $this->prepareUrl($this->project->id, $data['ticket'][3]->id, $this->company->id);

        $this->put($url, [
                'before_ticket_id' => $data['ticket'][1]->id,
                'sprint_id' => $data['ticket'][3]->sprint_id,
            ])
            ->seeStatusCode(200)
            ->isJson();

        $ticket_0 = $data['ticket'][0]->fresh();
        $ticket_1 = $data['ticket'][1]->fresh();
        $ticket_2 = $data['ticket'][2]->fresh();
        $ticket_3 = $data['ticket'][3]->fresh();

        $this->assertSame($data['ticket'][0]->sprint_id, $ticket_0->sprint_id);
        $this->assertSame($data['ticket'][0]->status_id, $ticket_0->status_id);
        $this->assertSame($data['ticket'][0]->priority, $ticket_0->priority);

        $this->assertSame($data['ticket'][1]->sprint_id, $ticket_1->sprint_id);
        $this->assertSame($data['ticket'][1]->status_id, $ticket_1->status_id);
        $this->assertSame($data['ticket'][1]->priority, $ticket_1->priority);

        $this->assertSame($data['ticket'][2]->sprint_id, $ticket_2->sprint_id);
        $this->assertSame($data['ticket'][2]->status_id, $ticket_2->status_id);
        $this->assertSame($data['ticket'][3]->priority, $ticket_2->priority);

        $this->assertSame($data['ticket'][3]->sprint_id, $ticket_3->sprint_id);
        $this->assertSame($data['ticket'][3]->status_id, $ticket_3->status_id);
        $this->assertSame($data['ticket'][2]->priority, $ticket_3->priority);

        //history
        $this->same_history(
            'priority',
            $data['ticket'][3]->id,
            $data['ticket'][3]->priority,
            null,
            $data['ticket'][2]->priority,
            null
        );
    }

    /**
     * @scenario Changing Of Ticket Priority
     *      @suit Changing Of Ticket Priority
     *      @case Success decrease position in sprint
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::changePriority
     * @test
     */
    public function changePriority_success_decrease_position_in_sprint()
    {
        $this->initEnv();
        $data = $this->prepareData();
        $url = $this->prepareUrl($this->project->id, $data['ticket'][1]->id, $this->company->id);

        $this->put($url, [
                'before_ticket_id' => $data['ticket'][2]->id,
                'sprint_id' => $data['ticket'][1]->sprint_id,
            ])
            ->seeStatusCode(200)
            ->isJson();

        $ticket_0 = $data['ticket'][0]->fresh();
        $ticket_1 = $data['ticket'][1]->fresh();
        $ticket_2 = $data['ticket'][2]->fresh();
        $ticket_3 = $data['ticket'][3]->fresh();

        $this->assertSame($data['ticket'][0]->sprint_id, $ticket_0->sprint_id);
        $this->assertSame($data['ticket'][0]->status_id, $ticket_0->status_id);
        $this->assertSame($data['ticket'][0]->priority, $ticket_0->priority);

        $this->assertSame($data['ticket'][1]->sprint_id, $ticket_1->sprint_id);
        $this->assertSame($data['ticket'][1]->status_id, $ticket_1->status_id);
        $this->assertSame($data['ticket'][2]->priority, $ticket_1->priority);

        $this->assertSame($data['ticket'][2]->sprint_id, $ticket_2->sprint_id);
        $this->assertSame($data['ticket'][2]->status_id, $ticket_2->status_id);
        $this->assertSame($data['ticket'][1]->priority, $ticket_2->priority);

        $this->assertSame($data['ticket'][3]->sprint_id, $ticket_3->sprint_id);
        $this->assertSame($data['ticket'][3]->status_id, $ticket_3->status_id);
        $this->assertSame($data['ticket'][3]->priority, $ticket_3->priority);

        //history
        $this->same_history(
            'priority',
            $data['ticket'][1]->id,
            $data['ticket'][1]->priority,
            null,
            $data['ticket'][2]->priority,
            null
        );
    }

    /**
     * @scenario Changing Of Ticket Priority
     *      @suit Changing Of Ticket Priority
     *      @case Success broadcasting
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::changePriority
     * @test
     */
    public function changePriority_success_broadcast()
    {
        Event::fake();

        $this->initEnv();
        $data = $this->prepareData();
        $url = $this->prepareUrl($this->project->id, $data['ticket'][1]->id, $this->company->id);

        $this->put($url, ['before_ticket_id' => null, 'sprint_id' => $data['sprints'][0]->id])
            ->seeStatusCode(200)
            ->isJson();

        Event::assertDispatched(ChangePriorityTicketEvent::class, function ($e) use ($data) {
            if (
                $e->project->id == $this->project->id &&
                $e->sprint_old_id == $data['ticket'][1]->sprint_id &&
                $e->sprint_new_id == $data['sprints'][0]->id &&
                $e->ticket->id == $data['ticket'][1]->id) {
                return true;
            }
        });
    }

    /**
     * @scenario Changing Of Ticket Priority
     *      @suit Changing Of Ticket Priority
     *      @case Success increasing position to first position of other sprint
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::changePriority
     * @test
     */
    public function changePriority_success_up_position_to_other_sprint_first()
    {
        $this->initEnv();
        $data = $this->prepareData();
        $url = $this->prepareUrl($this->project->id, $data['ticket'][1]->id, $this->company->id);

        $this->put($url, ['before_ticket_id' => null, 'sprint_id' => $data['sprints'][0]->id])
            ->seeStatusCode(200)
            ->isJson();

        $ticket_0 = $data['ticket'][0]->fresh();
        $ticket_1 = $data['ticket'][1]->fresh();
        $ticket_2 = $data['ticket'][2]->fresh();
        $ticket_3 = $data['ticket'][3]->fresh();

        $this->assertSame($data['ticket'][0]->sprint_id, $ticket_0->sprint_id);
        $this->assertSame($data['ticket'][0]->status_id, $ticket_0->status_id);
        $this->assertSame($data['ticket'][1]->priority, $ticket_0->priority);

        $this->assertSame($data['ticket'][0]->sprint_id, $ticket_1->sprint_id);
        $this->assertSame($data['ticket'][1]->status_id, $ticket_1->status_id);
        $this->assertSame($data['ticket'][0]->priority, $ticket_1->priority);

        $this->assertSame($data['ticket'][2]->sprint_id, $ticket_2->sprint_id);
        $this->assertSame($data['ticket'][2]->status_id, $ticket_2->status_id);
        $this->assertSame($data['ticket'][2]->priority, $ticket_2->priority);

        $this->assertSame($data['ticket'][3]->sprint_id, $ticket_3->sprint_id);
        $this->assertSame($data['ticket'][3]->status_id, $ticket_3->status_id);
        $this->assertSame($data['ticket'][3]->priority, $ticket_3->priority);

        //history
        $this->same_history(
            'priority',
            $data['ticket'][1]->id,
            $data['ticket'][1]->priority,
            null,
            $data['ticket'][0]->priority,
            null
        );
        $this->same_history(
            'sprint_id',
            $data['ticket'][1]->id,
            $data['sprints'][1]->id,
            $data['sprints'][1]->name,
            $data['sprints'][0]->id,
            $data['sprints'][0]->name
        );
    }

    /**
     * @scenario Changing Of Ticket Priority
     *      @suit Changing Of Ticket Priority
     *      @case Success increasing position to second position of other sprint
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::changePriority
     * @test
     */
    public function changePriority_success_up_position_to_other_sprint_second()
    {
        $this->initEnv();
        $data = $this->prepareData();
        $url = $this->prepareUrl($this->project->id, $data['ticket'][1]->id, $this->company->id);

        $this->put($url, [
                'before_ticket_id' => $data['ticket'][0]->id,
                'sprint_id' => $data['sprints'][0]->id,
            ])
            ->seeStatusCode(200)
            ->isJson();

        $ticket_0 = $data['ticket'][0]->fresh();
        $ticket_1 = $data['ticket'][1]->fresh();
        $ticket_2 = $data['ticket'][2]->fresh();
        $ticket_3 = $data['ticket'][3]->fresh();

        $this->assertSame($data['ticket'][0]->sprint_id, $ticket_0->sprint_id);
        $this->assertSame($data['ticket'][0]->status_id, $ticket_0->status_id);
        $this->assertSame($data['ticket'][0]->priority, $ticket_0->priority);

        $this->assertSame($data['ticket'][0]->sprint_id, $ticket_1->sprint_id);
        $this->assertSame($data['ticket'][1]->status_id, $ticket_1->status_id);
        $this->assertSame($data['ticket'][1]->priority, $ticket_1->priority);

        $this->assertSame($data['ticket'][2]->sprint_id, $ticket_2->sprint_id);
        $this->assertSame($data['ticket'][2]->status_id, $ticket_2->status_id);
        $this->assertSame($data['ticket'][2]->priority, $ticket_2->priority);

        $this->assertSame($data['ticket'][3]->sprint_id, $ticket_3->sprint_id);
        $this->assertSame($data['ticket'][3]->status_id, $ticket_3->status_id);
        $this->assertSame($data['ticket'][3]->priority, $ticket_3->priority);

        //history
        $this->same_history(
            'sprint_id',
            $data['ticket'][1]->id,
            $data['sprints'][1]->id,
            $data['sprints'][1]->name,
            $data['sprints'][0]->id,
            $data['sprints'][0]->name
        );
    }

    /**
     * @scenario Changing Of Ticket Priority
     *      @suit Changing Of Ticket Priority
     *      @case Success decreasing position to first position of other sprint
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::changePriority
     * @test
     */
    public function changePriority_success_down_position_to_other_sprint_first()
    {
        $this->initEnv();
        $data = $this->prepareData();
        $url = $this->prepareUrl($this->project->id, $data['ticket'][0]->id, $this->company->id);

        $this->put($url, ['before_ticket_id' => null, 'sprint_id' => $data['sprints'][1]->id])
            ->seeStatusCode(200)
            ->isJson();

        $ticket_0 = $data['ticket'][0]->fresh();
        $ticket_1 = $data['ticket'][1]->fresh();
        $ticket_2 = $data['ticket'][2]->fresh();
        $ticket_3 = $data['ticket'][3]->fresh();

        $this->assertSame($data['ticket'][1]->sprint_id, $ticket_0->sprint_id);
        $this->assertSame($data['ticket'][0]->status_id, $ticket_0->status_id);
        $this->assertSame($data['ticket'][0]->priority, $ticket_0->priority);

        $this->assertSame($data['ticket'][1]->sprint_id, $ticket_1->sprint_id);
        $this->assertSame($data['ticket'][1]->status_id, $ticket_1->status_id);
        $this->assertSame($data['ticket'][1]->priority, $ticket_1->priority);

        $this->assertSame($data['ticket'][2]->sprint_id, $ticket_2->sprint_id);
        $this->assertSame($data['ticket'][2]->status_id, $ticket_2->status_id);
        $this->assertSame($data['ticket'][2]->priority, $ticket_2->priority);

        $this->assertSame($data['ticket'][3]->sprint_id, $ticket_3->sprint_id);
        $this->assertSame($data['ticket'][3]->status_id, $ticket_3->status_id);
        $this->assertSame($data['ticket'][3]->priority, $ticket_3->priority);

        //history
        $this->same_history(
            'sprint_id',
            $data['ticket'][0]->id,
            $data['sprints'][0]->id,
            $data['sprints'][0]->name,
            $data['sprints'][1]->id,
            $data['sprints'][1]->name
        );
    }

    /**
     * @scenario Changing Of Ticket Priority
     *      @suit Changing Of Ticket Priority
     *      @case Success decreasing position to second position of other sprint
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::changePriority
     * @test
     */
    public function changePriority_success_down_position_to_other_sprint_second()
    {
        $this->initEnv();
        $data = $this->prepareData();
        $url = $this->prepareUrl($this->project->id, $data['ticket'][0]->id, $this->company->id);

        $this->put($url, [
                'before_ticket_id' => $data['ticket'][1]->id,
                'sprint_id' => $data['sprints'][1]->id,
            ])
            ->seeStatusCode(200)
            ->isJson();

        $ticket_0 = $data['ticket'][0]->fresh();
        $ticket_1 = $data['ticket'][1]->fresh();
        $ticket_2 = $data['ticket'][2]->fresh();
        $ticket_3 = $data['ticket'][3]->fresh();

        $this->assertSame($data['ticket'][1]->sprint_id, $ticket_0->sprint_id);
        $this->assertSame($data['ticket'][0]->status_id, $ticket_0->status_id);
        $this->assertSame($data['ticket'][1]->priority, $ticket_0->priority);

        $this->assertSame($data['ticket'][1]->sprint_id, $ticket_1->sprint_id);
        $this->assertSame($data['ticket'][1]->status_id, $ticket_1->status_id);
        $this->assertSame($data['ticket'][0]->priority, $ticket_1->priority);

        $this->assertSame($data['ticket'][2]->sprint_id, $ticket_2->sprint_id);
        $this->assertSame($data['ticket'][2]->status_id, $ticket_2->status_id);
        $this->assertSame($data['ticket'][2]->priority, $ticket_2->priority);

        $this->assertSame($data['ticket'][3]->sprint_id, $ticket_3->sprint_id);
        $this->assertSame($data['ticket'][3]->status_id, $ticket_3->status_id);
        $this->assertSame($data['ticket'][3]->priority, $ticket_3->priority);

        //history
        $this->same_history(
            'priority',
            $data['ticket'][0]->id,
            $data['ticket'][0]->priority,
            null,
            $data['ticket'][1]->priority,
            null
        );
        $this->same_history(
            'sprint_id',
            $data['ticket'][0]->id,
            $data['sprints'][0]->id,
            $data['sprints'][0]->name,
            $data['sprints'][1]->id,
            $data['sprints'][1]->name
        );
    }

    /**
     * @scenario Changing Of Ticket Priority
     *      @suit Changing Of Ticket Priority
     *      @case Success decreasing position to first position of backlog
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::changePriority
     * @test
     */
    public function changePriority_success_down_position_to_backlog_first()
    {
        $this->initEnv();
        $data = $this->prepareData();
        $data['ticket'][3]->sprint_id = 0;
        $data['ticket'][3]->save();
        $url = $this->prepareUrl($this->project->id, $data['ticket'][2]->id, $this->company->id);

        $this->put($url, ['before_ticket_id' => null, 'sprint_id' => 0])
            ->seeStatusCode(200)
            ->isJson();

        $ticket_0 = $data['ticket'][0]->fresh();
        $ticket_1 = $data['ticket'][1]->fresh();
        $ticket_2 = $data['ticket'][2]->fresh();
        $ticket_3 = $data['ticket'][3]->fresh();

        $this->assertSame($data['ticket'][0]->sprint_id, $ticket_0->sprint_id);
        $this->assertSame($data['ticket'][0]->status_id, $ticket_0->status_id);
        $this->assertSame($data['ticket'][0]->priority, $ticket_0->priority);

        $this->assertSame($data['ticket'][1]->sprint_id, $ticket_1->sprint_id);
        $this->assertSame($data['ticket'][1]->status_id, $ticket_1->status_id);
        $this->assertSame($data['ticket'][1]->priority, $ticket_1->priority);

        $this->assertSame(0, $ticket_2->sprint_id);
        $this->assertSame($data['ticket'][2]->status_id, $ticket_2->status_id);
        $this->assertSame($data['ticket'][2]->priority, $ticket_2->priority);

        $this->assertSame($data['ticket'][3]->sprint_id, $ticket_3->sprint_id);
        $this->assertSame($data['ticket'][3]->status_id, $ticket_3->status_id);
        $this->assertSame($data['ticket'][3]->priority, $ticket_3->priority);

        //history
        $this->same_history(
            'sprint_id',
            $data['ticket'][2]->id,
            $data['sprints'][1]->id,
            $data['sprints'][1]->name,
            0,
            'Backlog'
        );
    }

    /**
     * @scenario Changing Of Ticket Priority
     *      @suit Changing Of Ticket Priority
     *      @case Success decreasing position to second position of backlog
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::changePriority
     * @test
     */
    public function changePriority_success_down_position_to_backlog_second()
    {
        $this->initEnv();
        $data = $this->prepareData();
        $data['ticket'][3]->sprint_id = 0;
        $data['ticket'][3]->save();
        $url = $this->prepareUrl($this->project->id, $data['ticket'][2]->id, $this->company->id);

        $this->put($url, ['before_ticket_id' => $data['ticket'][3]->id, 'sprint_id' => 0])
            ->seeStatusCode(200)
            ->isJson();

        $ticket_0 = $data['ticket'][0]->fresh();
        $ticket_1 = $data['ticket'][1]->fresh();
        $ticket_2 = $data['ticket'][2]->fresh();
        $ticket_3 = $data['ticket'][3]->fresh();

        $this->assertSame($data['ticket'][0]->sprint_id, $ticket_0->sprint_id);
        $this->assertSame($data['ticket'][0]->status_id, $ticket_0->status_id);
        $this->assertSame($data['ticket'][0]->priority, $ticket_0->priority);

        $this->assertSame($data['ticket'][1]->sprint_id, $ticket_1->sprint_id);
        $this->assertSame($data['ticket'][1]->status_id, $ticket_1->status_id);
        $this->assertSame($data['ticket'][1]->priority, $ticket_1->priority);

        $this->assertSame(0, $ticket_2->sprint_id);
        $this->assertSame($data['ticket'][2]->status_id, $ticket_2->status_id);
        $this->assertSame($data['ticket'][3]->priority, $ticket_2->priority);

        $this->assertSame($data['ticket'][3]->sprint_id, $ticket_3->sprint_id);
        $this->assertSame($data['ticket'][3]->status_id, $ticket_3->status_id);
        $this->assertSame($data['ticket'][2]->priority, $ticket_3->priority);

        //history
        $this->same_history(
            'priority',
            $data['ticket'][2]->id,
            $data['ticket'][2]->priority,
            null,
            $data['ticket'][3]->priority,
            null
        );
        $this->same_history(
            'sprint_id',
            $data['ticket'][2]->id,
            $data['sprints'][1]->id,
            $data['sprints'][1]->name,
            0,
            'Backlog'
        );
    }

    /**
     * @scenario Changing Of Ticket Priority
     *      @suit Changing Of Ticket Priority
     *      @case Success increasing position to first of all changed statuses
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::changePriority
     * @test
     */
    public function changePriority_success_up_position_to_first_of_all_change_status()
    {
        $this->initEnv();
        $data = $this->prepareData();
        $before_ticket_realizations = TicketRealization::count();
        $url = $this->prepareUrl($this->project->id, $data['ticket'][1]->id, $this->company->id);

        $this->put($url, ['before_ticket_id' => null, 'status_id' => $data['status'][1]->id])
            ->seeStatusCode(200)
            ->isJson();

        $ticket_0 = $data['ticket'][0]->fresh();
        $ticket_1 = $data['ticket'][1]->fresh();
        $ticket_2 = $data['ticket'][2]->fresh();
        $ticket_3 = $data['ticket'][3]->fresh();

        $this->assertSame($data['ticket'][0]->sprint_id, $ticket_0->sprint_id);
        $this->assertSame($data['ticket'][0]->status_id, $ticket_0->status_id);
        $this->assertSame($data['ticket'][1]->priority, $ticket_0->priority);

        $this->assertSame($data['ticket'][1]->sprint_id, $ticket_1->sprint_id);
        $this->assertSame($data['status'][1]->id, $ticket_1->status_id);
        $this->assertSame($data['ticket'][0]->priority, $ticket_1->priority);

        $this->assertSame($data['ticket'][2]->sprint_id, $ticket_2->sprint_id);
        $this->assertSame($data['ticket'][2]->status_id, $ticket_2->status_id);
        $this->assertSame($data['ticket'][2]->priority, $ticket_2->priority);

        $this->assertSame($data['ticket'][3]->sprint_id, $ticket_3->sprint_id);
        $this->assertSame($data['ticket'][3]->status_id, $ticket_3->status_id);
        $this->assertSame($data['ticket'][3]->priority, $ticket_3->priority);

        //history
        $this->same_history(
            'priority',
            $data['ticket'][1]->id,
            $data['ticket'][1]->priority,
            null,
            $data['ticket'][0]->priority,
            null
        );

        $this->assertEquals($before_ticket_realizations + 1, TicketRealization::count());
        $this->assertSame(1, TicketRealization::where([
            'ticket_id' => $data['ticket'][1]->id,
            'user_id' => $this->user->id,
            'start_at' => $this->now->toDateTimeString(),
            'end_at' => null,
        ])->count());
    }

    /**
     * @param $project_id
     * @param $ticket_id
     * @param $company_id
     *
     * @return string
     */
    private function prepareUrl($project_id, $ticket_id, $company_id)
    {
        return "/projects/{$project_id}/tickets/{$ticket_id}/change-priority"
            . "?selected_company_id={$company_id}";
    }

    /**
     * @return mixed
     */
    private function prepareData()
    {
        $data['project_2'] = factory(Project::class)->create();

        $data['sprints'] [] = factory(Sprint::class)->create([
            'project_id' => $this->project->id,
            'priority' => 1,
            'name' => 'test',
            'status' => Sprint::INACTIVE,
        ]);
        $data['sprints'] [] = factory(Sprint::class)->create([
            'project_id' => $this->project->id,
            'priority' => 2,
            'name' => 'test',
            'status' => Sprint::ACTIVE,
        ]);
        $data['sprints'] [] = factory(Sprint::class)->create([
            'project_id' => $this->project->id,
            'priority' => 3,
            'name' => 'test',
            'status' => Sprint::ACTIVE,
        ]);

        $data['status'] [] = factory(Status::class)->create([
            'project_id' => $this->project->id,
            'priority' => 1,
        ]);
        $data['status'] [] = factory(Status::class)->create([
            'project_id' => $this->project->id,
            'priority' => 2,
        ]);

        $data['ticket'] [] = factory(Ticket::class)->create([
            'project_id' => $this->project->id,
            'sprint_id' => $data['sprints'][0]->id,
            'status_id' => $data['status'][0]->id,
            'priority' => 1,
            'hidden' => 0,
        ]);
        $data['ticket'] [] = factory(Ticket::class)->create([
            'project_id' => $this->project->id,
            'sprint_id' => $data['sprints'][1]->id,
            'status_id' => $data['status'][0]->id,
            'assigned_id' => $this->user->id,
            'priority' => 2,
            'hidden' => 0,
        ]);
        $data['ticket'] [] = factory(Ticket::class)->create([
            'project_id' => $this->project->id,
            'sprint_id' => $data['sprints'][1]->id,
            'status_id' => $data['status'][1]->id,
            'priority' => 3,
            'hidden' => 1,
        ]);
        $data['ticket'] [] = factory(Ticket::class)->create([
            'project_id' => $this->project->id,
            'sprint_id' => $data['sprints'][1]->id,
            'status_id' => $data['status'][0]->id,
            'priority' => 4,
            'hidden' => 1,
        ]);

        $this->project->update(['status_for_calendar_id' => $data['status'][1]->id]);

        History::whereRaw('1 = 1')->delete();

        return $data;
    }

    /**
     * @param $field_name
     * @param $ticket_id
     * @param $value_before
     * @param $label_before
     * @param $value_after
     * @param $label_after
     */
    private function same_history(
        $field_name,
        $ticket_id,
        $value_before,
        $label_before,
        $value_after,
        $label_after
    ) {
        $field_id = HistoryField::getId(HistoryService::TICKET, $field_name);

        $this->assertSame(1, History::where([
            'user_id' => $this->user->id,
            'resource_id' => $ticket_id,
            'object_id' => $ticket_id,
            'created_at' => Carbon::now()->toDateTimeString(),
            'field_id' => $field_id,
            'value_before' => $value_before,
            'label_before' => $label_before,
            'value_after' => $value_after,
            'label_after' => $label_after,
        ])->count());
    }
}
