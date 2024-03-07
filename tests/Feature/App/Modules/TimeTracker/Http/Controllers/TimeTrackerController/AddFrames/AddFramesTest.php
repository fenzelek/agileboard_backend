<?php

namespace Tests\Feature\App\Modules\TimeTracker\Http\Controllers\TimeTrackerController\AddFrames;

use App\Models\Db\Integration\IntegrationProvider;
use App\Models\Db\TimeTracker\Frame;
use Carbon\Carbon;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Tests\BrowserKitTestCase;
use Tests\TestCase;

class AddFramesTest extends BrowserKitTestCase
{
    use DatabaseTransactions;
    use TestTrait;

    /**
     * @feature Time Tracker
     * @scenario Add frame
     * @case Missing required collection of frames
     *
     * @test
     * @covers \App\Modules\TimeTracker\Http\Controllers\TimeTrackerController::addFrames
     */
    public function addFrames_check_validation_for_required_collection()
    {
        // GIVEN
        $this->createUser();
        $company = $this->prepareCompany();
        $this->assignUsersToCompany(collect([$this->user]), $company);
        $this->be($this->user);

        // WHEN
        $this->post(route('time-tracker.add-frames'), []);

        //THEN
        $this->verifyValidationResponse([
            'frames',
        ]);
    }

    /**
     * @feature Time Tracker
     * @scenario Add frame
     * @case Missing required fields for sent frame
     *
     * @test
     * @covers \App\Modules\TimeTracker\Http\Controllers\TimeTrackerController::addFrames
     */
    public function addFrames_check_validation_for_required_fields_of_sent_frame()
    {
        // GIVEN
        $this->createUser();
        $company = $this->prepareCompany();
        $this->assignUsersToCompany(collect([$this->user]), $company);
        $this->be($this->user);

        // WHEN
        $this->post(route('time-tracker.add-frames'), [
            'frames' => [
                [],
            ],
        ]);

        //THEN
        $this->verifyValidationResponse([
            'frames.0.from',
            'frames.0.to',
            'frames.0.companyId',
            'frames.0.projectId',
            'frames.0.activity',
        ]);
    }

    /**
     * @feature Time Tracker
     * @scenario Add frame
     * @case Show validation error message when "from" value is grater than "to" value
     *
     * @test
     * @covers \App\Modules\TimeTracker\Http\Controllers\TimeTrackerController::addFrames
     *
     * @dataProvider provideInvalidFramePeriod
     */
    public function addFrames_check_validation_when_from_field_is_grater_than_to_field(int $from, int $to)
    {
        // GIVEN
        $this->createUser();
        $company = $this->prepareCompany();
        $this->assignUsersToCompany(collect([$this->user]), $company);
        $this->be($this->user);

        // WHEN
        $this->post(route('time-tracker.add-frames'), [
            'frames' => [
                [
                    'from' => $from,
                    'to' => $to,
                ],
            ],
        ]);

        // THEN
        $this->verifyValidationResponse([
            'frames.0.to',
        ]);
    }

    /**
     * @feature Time Tracker
     * @scenario Add frame
     * @case Check proper response structure
     *
     * @test
     * @covers \App\Modules\TimeTracker\Http\Controllers\TimeTrackerController::addFrames
     */
    public function addFrames_check_one_frame_was_added()
    {
        // GIVEN
        $this->createUser();
        $this->be($this->user);

        $company = $this->prepareCompany();
        $project = $this->prepareProject($company);
        $ticket = $this->prepareTicket($project);

        $company->integrations()->create([
            'integration_provider_id' => IntegrationProvider::findBySlug(IntegrationProvider::TIME_TRACKER)->id,
        ]);

        $this->assignUsersToCompany(collect([$this->user]), $company);

        // WHEN
        $this->post(route('time-tracker.add-frames'), [
            'frames' => [
                [
                    'from' => Carbon::now()->setHour(8)->subMinutes(10)->getTimestamp(),
                    'to' => Carbon::now()->setHour(8)->getTimestamp(),
                    'companyId' => $company->id,
                    'projectId' => $project->id,
                    'activity' => 10,
                    'taskId' => $ticket->id,
                    'screens' => [],
                ],
                [
                    'from' => Carbon::now()->setHour(7)->subMinutes(10)->getTimestamp(),
                    'to' => Carbon::now()->setHour(7)->getTimestamp(),
                    'companyId' => $company->id,
                    'projectId' => $project->id,
                    'activity' => 40,
                    'taskId' => $ticket->id,
                    'screens' => [],
                    "gpsPosition" =>  [
                        "latitude" => 52.4076,
                        "longitude" => 16.9082
                        ],
                ],
            ],
        ]);

        // THEN
        $response = $this->decodeResponseJson();
        $this->assertResponseStatus(201);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('companies', $response['data']);
        $this->assertArrayHasKey('projects', $response['data']);
        $this->assertArrayHasKey('tickets', $response['data']);
    }

    /**
     * @feature Time Tracker
     * @scenario Add frame
     * @case Frame was rejected
     *
     * @test
     * @covers \App\Modules\TimeTracker\Http\Controllers\TimeTrackerController::addFrames
     *
     * @dataProvider provideTooLongFramePeriod
     */
    public function addFrames_frames_was_rejected($from, int $to)
    {
        // GIVEN
        $this->createUser();
        $this->be($this->user);

        $company = $this->prepareCompany();
        $project = $this->prepareProject($company);
        $ticket = $this->prepareTicket($project);

        $company->integrations()->create([
            'integration_provider_id' => IntegrationProvider::findBySlug(IntegrationProvider::TIME_TRACKER)->id,
        ]);

        $this->assignUsersToCompany(collect([$this->user]), $company);

        // WHEN
        $this->post(route('time-tracker.add-frames'), [
            'frames' => [
                [
                    'from' => $from,
                    'to' => $to,
                    'companyId' => $company->id,
                    'projectId' => $project->id,
                    'activity' => 10,
                    'taskId' => $ticket->id,
                    'screens' => [],
                ],
            ],
        ]);

        // THEN
        $response = $this->decodeResponseJson();
        $this->assertResponseStatus(201);
        $this->assertSame($from, Arr::get($response, 'data.reject_frames.0.from'));
        $this->assertSame($to, Arr::get($response, 'data.reject_frames.0.to'));
    }

    /**
     * @feature Time Tracker
     * @scenario Add frame
     * @case Check proper response structure, frame not added to DB
     *
     * @test
     * @covers \App\Modules\TimeTracker\Http\Controllers\TimeTrackerController::addFrames
     */
    public function addFrames_check_check_frame_wasnt_add_to_DB()
    {
        // GIVEN
        $this->createUser();
        $this->be($this->user);

        $company = $this->prepareCompany();
        $project = $this->prepareProject($company);
        $ticket = $this->prepareTicket($project);

        $this->assignUsersToCompany(collect([$this->user]), $company);

        $from = Carbon::now()->setHour(8)->subMinutes(10)->getTimestamp();
        $to = Carbon::now()->setHour(8)->getTimestamp();

        factory(Frame::class)->create([
            'user_id' => $this->user->id,
            'from' => $from,
            'to' => $to,
            'project_id' => $project->id,
            'activity' => 10,
            'ticket_id' => $ticket->id,
            'screens' => [],
        ]);

        // WHEN
        $this->post(route('time-tracker.add-frames'), [
            'frames' => [
                [
                    'from' => $from,
                    'to' => $to,
                    'companyId' => $company->id,
                    'projectId' => $project->id,
                    'activity' => 10,
                    'taskId' => $ticket->id,
                    'screens' => [],
                ],
            ],
        ]);

        // THEN
        $response = $this->decodeResponseJson();
        $this->assertResponseStatus(201);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('companies', $response['data']);
        $this->assertArrayHasKey('projects', $response['data']);
        $this->assertArrayHasKey('tickets', $response['data']);
    }

    public function provideTooLongFramePeriod()
    {
        return [
            [
                'from' => Carbon::now()->setHour(8)->subDay()->getTimestamp(),
                'to' => Carbon::now()->setHour(8)->getTimestamp(),
            ],
        ];
    }

    public function provideInvalidFramePeriod()
    {
        return [
            [
                'from' => Carbon::now()->setHour(10)->getTimestamp(),
                'to' => Carbon::now()->setHour(8)->getTimestamp(),
            ],
            [
                'from' => Carbon::parse("2022-11-22 00:00:00")->getTimestamp(),
                'to' => Carbon::parse("2022-11-21 23:59:59")->getTimestamp(),
            ],

        ];
    }
}
