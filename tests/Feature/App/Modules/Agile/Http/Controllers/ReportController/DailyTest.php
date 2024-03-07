<?php

namespace Tests\Feature\App\Modules\Agile\Http\Controllers\ReportController;

use App\Helpers\ErrorCode;
use App\Models\Db\History;
use App\Models\Db\HistoryField;
use App\Models\Db\Ticket;
use App\Models\Other\RoleType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Helpers\ProjectHelper;
use Tests\Helpers\ResponseHelper;
use Tests\BrowserKitTestCase;

class DailyTest extends BrowserKitTestCase
{
    use DatabaseTransactions, ProjectHelper, ResponseHelper, TestTrait;

    /**
     * @scenario Daily Report based on tickets history
     *      @suit Daily Report
     *      @case Return error when user hasn't permission
     *
     * @covers \App\Modules\Agile\Http\Controllers\ReportController::daily
     * @dataProvider rolesProvider
     * @test
     */
    public function daily_report_error_has_not_permission($role)
    {
        $this->initEnv($role);

        $url = $this->prepareUrl($this->project->id, $this->company->id, $this->date_from, $this->date_to);

        $this->get($url, []);
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /**
     * @scenario Daily Report based on tickets history
     *      @suit Daily Report
     *      @case Return error when provided data are incorrect
     *
     * @covers \App\Modules\Agile\Http\Controllers\ReportController::daily
     * @test
     */
    public function daily_report_validation_error()
    {
        $this->initEnv();

        $from = 'some_incorrect_date';
        $to = 'some_incorrect_date';

        $url = $this->prepareUrl($this->project->id, $this->company->id, $from, $to);

        $this->get($url, []);
        $this->verifyErrorResponse(422, ErrorCode::VALIDATION_FAILED, [
            'date_from',
            'date_to',
        ]);
    }

    /**
     * @scenario Daily Report based on tickets history
     *      @suit Daily Report
     *      @case Return error when project not exist
     *
     * @covers \App\Modules\Agile\Http\Controllers\ReportController::daily
     * @test
     */
    public function daily_report_error_project_not_exist()
    {
        $this->initEnv();
        $url = $this->prepareUrl(-1, $this->company->id, $this->date_from, $this->date_to);

        $this->get($url, []);
        $this->verifyErrorResponse(404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /**
     * @scenario Daily Report based on tickets history
     *      @suit Daily Report
     *      @case Success for specific project
     *
     * @covers \App\Modules\Agile\Http\Controllers\ReportController::daily
     * @test
     */
    public function daily_report_success_response_for_specific_project()
    {
        $role = RoleType::ADMIN;
        $this->initEnv($role);

        $project_2 = $this->createProject('PROJ2');
        $this->setProjectRole($project_2, $role);

        $ticket2 = factory(Ticket::class)->create([
            'project_id' => $this->project->id,
        ]);
        $ticket3 = factory(Ticket::class)->create([
            'project_id' => $project_2->id,
        ]);
        $url = $this->prepareUrl($this->project->id, $this->company->id, $this->date_from, $this->date_to);

        History::query()->delete();

        $field = HistoryField::limit(3)->get();
        $history1 = factory(History::class)->create([
            'user_id' => $this->user->id,
            'resource_id' => $ticket2->id,
            'object_id' => $ticket2->id,
            'field_id' => $field[0]->id,
            'created_at' => $this->now,
        ]);
        $history2 = factory(History::class)->create([
            'user_id' => $this->user->id,
            'resource_id' => $ticket2->id,
            'object_id' => $ticket2->id,
            'field_id' => $field[1]->id,
            'created_at' => $this->now,
        ]);
        $data['history3'] = factory(History::class)->create([
            'user_id' => $this->user->id,
            'resource_id' => $ticket3->id,
            'object_id' => $ticket2->id,
            'field_id' => $field[2]->id,
            'created_at' => $this->now,
        ]);

        $this->get($url)
            ->seeStatusCode(200);

        $response_history = $this->decodeResponseJson()['data'];
        $this->assertCount(2, $response_history);

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
     * @scenario Daily Report based on tickets history
     *      @suit Daily Report
     *      @case Success for all projects
     *
     * @covers \App\Modules\Agile\Http\Controllers\ReportController::daily
     * @test
     */
    public function daily_report_success_response_for_all_projects()
    {
        $role = RoleType::ADMIN;
        $this->initEnv($role);

        $project_2 = $this->createProject('PROJ2');
        $this->setProjectRole($project_2, $role);

        $ticket2 = factory(Ticket::class)->create([
            'project_id' => $project_2->id,
        ]);
        $url = $this->prepareUrl(null, $this->company->id, $this->date_from, $this->date_to);

        History::query()->delete();

        $field = HistoryField::limit(3)->get();
        $history1 = factory(History::class)->create([
            'user_id' => $this->user->id,
            'resource_id' => $ticket2->id,
            'object_id' => $ticket2->id,
            'field_id' => $field[0]->id,
            'created_at' => $this->now,
        ]);
        $history2 = factory(History::class)->create([
            'user_id' => $this->user->id,
            'resource_id' => $ticket2->id,
            'object_id' => $ticket2->id,
            'field_id' => $field[1]->id,
            'created_at' => $this->now,
        ]);
        $history_3 = factory(History::class)->create([
            'user_id' => $this->user->id,
            'resource_id' => $ticket2->id,
            'object_id' => $ticket2->id,
            'field_id' => $field[2]->id,
            'created_at' => $this->now,
        ]);

        $this->get($url)
            ->seeStatusCode(200);

        $response_history = $this->decodeResponseJson()['data'];
        $this->assertCount(3, $response_history);

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

        $this->assertSame($history_3->id, $response_history[2]['id']);
        $this->assertSame($history_3->user_id, $response_history[2]['user_id']);
        $this->assertSame($history_3->resource_id, $response_history[2]['resource_id']);
        $this->assertSame($history_3->object_id, $response_history[2]['object_id']);
        $this->assertSame($history_3->field_id, $response_history[2]['field_id']);
        $this->assertSame($history_3->value_before, $response_history[2]['value_before']);
        $this->assertSame($history_3->label_before, $response_history[2]['label_before']);
        $this->assertSame($history_3->value_after, $response_history[2]['value_after']);
        $this->assertSame($history_3->label_after, $response_history[2]['label_after']);
        $this->assertSame($this->user->id, $response_history[2]['user']['data']['id']);
        $this->assertSame($field[2]->id, $response_history[2]['field']['data']['id']);
    }

    /**
     * @return array
     */
    public function rolesProvider()
    {
        return [
            'roles' => [
                RoleType::EMPLOYEE,
                RoleType::CLIENT,
                RoleType::API_COMPANY,
                RoleType::TAX_OFFICE,
            ],
        ];
    }

    /**
     * @param $project_id
     * @param $company_id
     *
     * @param $from
     * @param $to
     * @return string
     */
    private function prepareUrl($project_id, $company_id, $from, $to)
    {
        $url = "/reports/tickets/daily?selected_company_id={$company_id}&date_from={$from}&date_to={$to}";

        if (null !== $project_id) {
            $url .= "&project_id={$project_id}";
        }

        return $url;
    }
}
