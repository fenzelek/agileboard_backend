<?php

namespace Tests\Feature\App\Modules\Agile\Http\Controllers\TicketController;

use App\Helpers\ErrorCode;
use App\Models\Db\History;
use App\Models\Db\HistoryField;
use App\Models\Db\Project;
use App\Models\Db\Ticket;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Helpers\ProjectHelper;
use Tests\Helpers\ResponseHelper;
use Tests\BrowserKitTestCase;

class HistoryTest extends BrowserKitTestCase
{
    use DatabaseTransactions, ProjectHelper, ResponseHelper, TestTrait;

    /**
     * @scenario Tickets history
     *      @suit Tickets history
     *      @case Return error when user hasn't permission
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::history
     * @test
     */
    public function history_error_has_not_permission()
    {
        $this->initEnv();

        $project_2 = factory(Project::class)->create(['company_id' => $this->company->id]);
        $ticket = factory(Ticket::class)->create(['project_id' => $project_2->id]);
        $url = $this->prepareUrl($this->project->id, $ticket->id, $this->company->id);

        $this->get($url, []);
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /**
     * @scenario Tickets history
     *      @suit Tickets history
     *      @case Return error when ticket not exist
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::history
     * @test
     */
    public function history_error_ticket_not_exist()
    {
        $this->initEnv();
        $url = $this->prepareUrl($this->project->id, 0, $this->company->id);

        $this->get($url, []);
        $this->verifyErrorResponse(404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /**
     * @scenario Tickets history
     *      @suit Tickets history
     *      @case Success
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::history
     * @test
     */
    public function history_success_response()
    {
        $this->initEnv();

        $ticket2 = factory(Ticket::class)->create([
            'project_id' => $this->project->id,
        ]);
        $url = $this->prepareUrl($this->project->id, $ticket2->id, $this->company->id);

        History::whereRaw('1 = 1')->delete();

        $field = HistoryField::limit(3)->get();
        $history1 = factory(History::class)->create([
            'user_id' => $this->user->id,
            'resource_id' => $ticket2->id,
            'object_id' => $ticket2->id,
            'field_id' => $field[0]->id,
        ]);
        $history2 = factory(History::class)->create([
            'user_id' => $this->user->id,
            'resource_id' => $ticket2->id,
            'object_id' => $ticket2->id,
            'field_id' => $field[1]->id,
        ]);
        $data['history3'] = factory(History::class)->create([
            'user_id' => $this->user->id,
            'resource_id' => $ticket2->id,
            'object_id' => $ticket2->id,
            'field_id' => $field[2]->id,
        ]);

        $this->get($url . '&limit=2')
            ->seeStatusCode(200);

        $response_history = $this->decodeResponseJson()['data'];
        $this->assertSame($history1->id, $response_history[0]['id']);
        $this->assertSame($history1->user_id, $response_history[0]['user_id']);
        $this->assertSame($history1->resource_id, $response_history[0]['resource_id']);
        $this->assertSame($history1->object_id, $response_history[0]['object_id']);
        $this->assertSame($history1->field_id, $response_history[0]['field_id']);
        $this->assertSame($history1->value_before, $response_history[0]['value_before']);
        $this->assertSame($history1->label_before, $response_history[0]['label_before']);
        $this->assertSame($history1->value_after, $response_history[0]['value_after']);
        $this->assertSame($history1->label_after, $response_history[0]['label_after']);
        $this->assertSame($this->user->id, $response_history[0]['user']['data']['id']);
        $this->assertSame($field[0]->id, $response_history[0]['field']['data']['id']);
        $this->assertSame($history2->id, $response_history[1]['id']);
        $this->assertSame($history2->user_id, $response_history[1]['user_id']);
        $this->assertSame($history2->resource_id, $response_history[1]['resource_id']);
        $this->assertSame($history2->object_id, $response_history[1]['object_id']);
        $this->assertSame($history2->field_id, $response_history[1]['field_id']);
        $this->assertSame($history2->value_before, $response_history[1]['value_before']);
        $this->assertSame($history2->label_before, $response_history[1]['label_before']);
        $this->assertSame($history2->value_after, $response_history[1]['value_after']);
        $this->assertSame($history2->label_after, $response_history[1]['label_after']);
        $this->assertSame($this->user->id, $response_history[1]['user']['data']['id']);
        $this->assertSame($field[1]->id, $response_history[1]['field']['data']['id']);
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
        return "/projects/{$project_id}/tickets/{$ticket_id}/history?selected_company_id={$company_id}";
    }
}
