<?php

namespace Tests\Feature\App\Modules\Agile\Http\Controllers\TicketController;

use App\Helpers\ErrorCode;
use App\Models\Db\History;
use App\Models\Db\HistoryField;
use App\Models\Db\Project;
use App\Models\Db\Sprint;
use App\Models\Db\Ticket;
use App\Modules\Agile\Events\SetFlagToHideTicketEvent;
use App\Modules\Agile\Services\HistoryService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Tests\Helpers\ProjectHelper;
use Tests\Helpers\ResponseHelper;
use Tests\BrowserKitTestCase;

class SetFlagToHideTest extends BrowserKitTestCase
{
    use DatabaseTransactions, ProjectHelper, ResponseHelper, TestTrait;

    protected function setUp():void
    {
        parent::setUp();
        $this->initEnv();
    }

    /**
     * @scenario Tickets - set flag to hide
     *      @suit Tickets - set flag to hide
     *      @case Return error when user hasn't permission
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::history
     * @test
     */
    public function setFlagToHide_error_has_not_permission()
    {
        $project_2 = factory(Project::class)->create(['company_id' => $this->company->id]);
        $ticket = factory(Ticket::class)->create(['project_id' => $project_2->id]);
        $url = $this->prepareUrl($this->project->id, $ticket->id, $this->company->id);

        $this->put($url, []);
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /**
     * @scenario Tickets - set flag to hide
     *      @suit Tickets - set flag to hide
     *      @case Return error when ticket not exist
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::history
     * @test
     */
    public function setFlagToHide_error_ticket_not_exist()
    {
        $url = $this->prepareUrl($this->project->id, 0, $this->company->id);

        $this->put($url, []);
        $this->verifyErrorResponse(404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /**
     * @scenario Tickets - set flag to hide
     *      @suit Tickets - set flag to hide
     *      @case Check data in database
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::history
     * @test
     */
    public function setFlagToHide_success_db()
    {
        $ticket2 = factory(Ticket::class)->create([
            'project_id' => $this->project->id,
            'priority' => 2,
            'hidden' => false,
        ]);
        $url = $this->prepareUrl($this->project->id, $ticket2->id, $this->company->id);

        $before_tickets = Ticket::count();
        $before_history = History::count();

        $this->put($url, [])
            ->seeStatusCode(200);

        $this->assertEquals($before_tickets, Ticket::count());

        $ticket = $ticket2->fresh();
        $this->assertSame($ticket2->id, $ticket->id);
        $this->assertSame($ticket2->project_id, $ticket->project_id);
        $this->assertSame($ticket2->sprint_id, $ticket->sprint_id);
        $this->assertSame($ticket2->status_id, $ticket->status_id);
        $this->assertSame($ticket2->name, $ticket->name);
        $this->assertSame($ticket2->title, $ticket->title);
        $this->assertSame($ticket2->type_id, $ticket->type_id);
        $this->assertSame($ticket2->assigned_id, $ticket->assigned_id);
        $this->assertSame($ticket2->reporter_id, $ticket->reporter_id);
        $this->assertSame($ticket2->description, $ticket->description);
        $this->assertSame($ticket2->estimate_time, $ticket->estimate_time);
        $this->assertSame($ticket2->priority, $ticket->priority);
        $this->assertSame(1, $ticket->hidden);
        $this->assertSame(
            $this->now->toDateTimeString(),
            $ticket->created_at->toDateTimeString()
        );
        $this->assertSame(
            $this->now->toDateTimeString(),
            $ticket->updated_at->toDateTimeString()
        );

        //history
        $this->assertEquals($before_history + 1, History::count());
        $history = History::latest('id')->first();
        $this->assertSame($this->user->id, $history->user_id);
        $this->assertSame($ticket2->id, $history->resource_id);
        $this->assertSame($ticket2->id, $history->object_id);
        $this->assertSame(
            HistoryField::getId(HistoryService::TICKET, 'hidden'),
            $history->field_id
        );
        $this->assertEquals(0, $history->value_before);
        $this->assertSame(null, $history->label_before);
        $this->assertEquals(1, $history->value_after);
        $this->assertSame(null, $history->label_after);
        $this->assertSame(
            $this->now->toDateTimeString(),
            $history->created_at->toDateTimeString()
        );
    }

    /**
     * @scenario Tickets - set flag to hide
     *      @suit Tickets - set flag to hide
     *      @case Success
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::history
     * @test
     */
    public function setFlagToHide_success_response()
    {
        Event::fake();

        $sprint = factory(Sprint::class)->create();
        $ticket2 = factory(Ticket::class)->create([
            'project_id' => $this->project->id,
            'sprint_id' => $sprint->id,
            'priority' => 2,
            'hidden' => false,
        ]);
        $url = $this->prepareUrl($this->project->id, $ticket2->id, $this->company->id);

        $this->put($url, [])
            ->seeStatusCode(200);

        Event::assertDispatched(SetFlagToHideTicketEvent::class, function ($e) use ($ticket2) {
            $sprint_id = $e->sprint ? $e->sprint->id : 0;
            if (
                $e->project->id == $this->project->id &&
                $e->ticket->id == $ticket2->id &&
                $sprint_id == $ticket2->sprint_id) {
                return true;
            }
        });

        $response_ticket = $this->decodeResponseJson()['data'];
        $this->assertSame($ticket2->id, $response_ticket['id']);
        $this->assertSame($ticket2->project_id, $response_ticket['project_id']);
        $this->assertSame($ticket2->name, $response_ticket['name']);
        $this->assertSame($ticket2->title, $response_ticket['title']);
        $this->assertSame($ticket2->type_id, $response_ticket['type_id']);
        $this->assertSame($ticket2->assigned_id, $response_ticket['assigned_id']);
        $this->assertSame($ticket2->reporter_id, $response_ticket['reporter_id']);
        $this->assertSame($ticket2->description, $response_ticket['description']);
        $this->assertSame($ticket2->estimate_time, $response_ticket['estimate_time']);
        $this->assertSame($ticket2->priority, $response_ticket['priority']);
        $this->assertSame(true, $response_ticket['hidden']);
        $this->assertSame($this->now->toDateTimeString(), $response_ticket['created_at']);
        $this->assertSame($this->now->toDateTimeString(), $response_ticket['updated_at']);
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
        return "/projects/{$project_id}/tickets/{$ticket_id}/hide?selected_company_id={$company_id}";
    }
}
