<?php

namespace Tests\Feature\App\Modules\TimeTracker\Http\Controllers\TimeTrackerController\GetTimeSummary;

use App\Models\Db\Integration\TimeTracking\Activity;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BrowserKitTestCase;

class GetTimeSummaryTest extends BrowserKitTestCase
{
    use DatabaseTransactions, TestTrait;

    /**
     * @feature Time Tracker
     * @scenario Get time summary
     * @case Get proper response
     *
     * @test
     * @covers \App\Modules\TimeTracker\Http\Controllers\TimeTrackerController::getTimeSummary
     */
    public function getTimeSummary_check_response()
    {
        // GIVEN
        $this->createUser();
        $this->be($this->user);

        $company = $this->prepareCompany();
        $project = $this->prepareProject($company);
        $ticket = $this->prepareTicket($project);

        $this->assignUsersToCompany(collect([$this->user]), $company);
        Activity::query()->create([
            'utc_started_at' => Carbon::now()->setHour(8)->subMinutes(10),
            'utc_finished_at' => Carbon::now()->setHour(8),
            'user_id' => $this->user->id,
            'project_id' => $project->id,
            'ticket_id' => $ticket->id,
            'activity' => 10,
            'tracked' => 10,
        ]);

        // WHEN
        $this->get(route('time-tracker.time-summary'));

        // THEN
        $response = $this->decodeResponseJson();
        $this->assertResponseStatus(200);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('companies', $response['data']);
        $this->assertArrayHasKey('projects', $response['data']);
        $this->assertArrayHasKey('tickets', $response['data']);

        $this->assertArrayHasKey($company->id, $response['data']['companies']);
        $this->assertArrayHasKey("{$company->id}:{$project->id}", $response['data']['projects']);
        $this->assertArrayHasKey(
            "{$company->id}:{$project->id}:{$ticket->id}",
            $response['data']['tickets']
        );

        $this->assertIsInt($response['data']['companies'][$company->id]);
        $this->assertIsInt($response['data']['projects']["{$company->id}:{$project->id}"]);
        $this->assertIsInt($response['data']['tickets']["{$company->id}:{$project->id}:{$ticket->id}"]);
    }

    /**
     * @feature Time Tracker
     * @scenario Get time summary
     * @case Get proper response with time zone offset input
     *
     * @test
     * @covers \App\Modules\TimeTracker\Http\Controllers\TimeTrackerController::getTimeSummary
     */
    public function getTimeSummary_getProperResponseWithTimeZoneOffsetInput()
    {
        // GIVEN
        $this->createUser();
        $this->be($this->user);

        $company = $this->prepareCompany();
        $project = $this->prepareProject($company);
        $ticket = $this->prepareTicket($project);

        $this->assignUsersToCompany(collect([$this->user]), $company);
        Activity::query()->create([
            'utc_started_at' => Carbon::now()->setHour(8)->subMinutes(10),
            'utc_finished_at' => Carbon::now()->setHour(8),
            'user_id' => $this->user->id,
            'project_id' => $project->id,
            'ticket_id' => $ticket->id,
            'activity' => 10,
            'tracked' => 10,
        ]);

        // WHEN
        $this->getJson(route('time-tracker.time-summary', ['time_zone_offset' => '1']));

        // THEN
        $response = $this->decodeResponseJson();
        $this->assertResponseStatus(200);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('companies', $response['data']);
        $this->assertArrayHasKey('projects', $response['data']);
        $this->assertArrayHasKey('tickets', $response['data']);

        $this->assertArrayHasKey($company->id, $response['data']['companies']);
        $this->assertArrayHasKey("{$company->id}:{$project->id}", $response['data']['projects']);
        $this->assertArrayHasKey(
            "{$company->id}:{$project->id}:{$ticket->id}",
            $response['data']['tickets']
        );

        $this->assertIsInt($response['data']['companies'][$company->id]);
        $this->assertIsInt($response['data']['projects']["{$company->id}:{$project->id}"]);
        $this->assertIsInt($response['data']['tickets']["{$company->id}:{$project->id}:{$ticket->id}"]);
    }
}
