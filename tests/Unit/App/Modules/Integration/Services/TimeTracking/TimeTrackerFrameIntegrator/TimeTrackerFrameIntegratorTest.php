<?php

namespace Tests\Unit\App\Modules\Integration\Services\TimeTracking\TimeTrackerFrameIntegrator;

use App\Models\Db\Integration\IntegrationProvider;
use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\TimeTracker\ActivityFrameScreen;
use App\Models\Db\TimeTracker\Frame;
use App\Modules\Integration\Services\TimeTrackerFrameIntegrator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Tests\TestCase;

class TimeTrackerFrameIntegratorTest extends TestCase
{
    use DatabaseTransactions;
    use TimeTrackerFrameIntegratorTrait;

    /**
     * @var TimeTrackerFrameIntegrator
     */
    private $time_tracker_frame_integrator;

    public function setUp(): void
    {
        parent::setUp();
        \Auth::shouldReceive('id')->andReturn(1);
        $this->createUser();

        $this->time_tracker_frame_integrator = $this->app->make(TimeTrackerFrameIntegrator::class);
    }

    /**
     * @feature Integration
     * @scenario Save User Activity from Time Tracker
     * @case User Activity wasnt Stored because company hasnt active integration with time tracker
     *
     * @test
     */
    public function addActivity_new_activity_wasnt_stored()
    {
        //Given
        $tracked_time = 1000;
        $attributes = [
            'from' => 1600000000,
            'to' => 1600000000 + $tracked_time,
            'activity' => 100,
        ];

        $company = $this->createCompany();
        $project = $this->createProject($company);
        $ticket = $this->createTicket();
        $frame = $this->createFrame($project, $ticket, $attributes);

        //When
        $this->time_tracker_frame_integrator->addActivity($frame);

        //Then
        $this->assertDatabaseMissing('time_tracking_activities', [
            'external_activity_id' => $frame->id,
        ]);
    }

    /**
     * @feature Integration
     * @scenario Save User Activity from Time Tracker
     * @case User Activity wasnt Stored in DB, duplicate Activity
     *
     * @test
     */
    public function addActivity_new_activity_wasnt_stored_duplicate_activity()
    {
        //Given
        $tracked_time = 1000;
        $attributes = [
            'from' => 1600000000,
            'to' => 1600000000 + $tracked_time,
            'activity' => 100,
        ];

        $company = $this->createCompany();
        $project = $this->createProject($company);
        $ticket = $this->createTicket();
        $frame = $this->createFrame($project, $ticket, $attributes);
        $integration = $this->createIntegrationFor(IntegrationProvider::TIME_TRACKER, $company);

        $activity_db = $this->createDBActivity($tracked_time, $project, $ticket);
        $frame_db = $this->createDBFrame($tracked_time, $project, $ticket);

        $screens_names = ['1_1630667363_1.jpg', '1_1630667363_2.jpg'];
        $screens = $this->screenDBCreator($screens_names, $frame);

        $this->createActivityFrameScreenRelations($activity_db, $frame_db, $screens);

        //When
        $this->time_tracker_frame_integrator->addActivity($frame);

        //Then
        $this->assertDatabaseHas('time_tracking_activities', [
            'user_id' => $this->user->id,
            'project_id' => $project->id,
            'ticket_id' => $ticket->id,
            'utc_started_at' => Carbon::createFromTimestamp($attributes['from']),
            'utc_finished_at' => Carbon::createFromTimestamp($attributes['to']),
            'tracked' => $tracked_time,
            'activity' => $attributes['activity'],
            'deleted_at' => null,
        ]);

        $this->assertDatabaseHas('time_tracker_frames', [
            'user_id' => $this->user->id,
            'project_id' => $project->id,
            'ticket_id' => $ticket->id,
            'activity' => $attributes['activity'],
        ]);
        $this->assertDatabaseCount('time_tracker_frames', 2);
        $this->assertDatabaseCount('time_tracking_activities', 1);
        $this->assertDatabaseCount('time_tracker_activity_frame_screen', 6);
    }

    /**
     * @feature Integration
     * @scenario Save User Activity from Time Tracker
     * @case User Activity was Stored in DB
     *
     * @test
     */
    public function addActivity_new_activity_was_stored()
    {
        //Given
        $tracked_time = 1000;
        $attributes = [
            'from' => 1600000000,
            'to' => 1600000000 + $tracked_time,
            'activity' => 100,
        ];

        $company = $this->createCompany();
        $project = $this->createProject($company);
        $ticket = $this->createTicket();
        $frame = $this->createFrame($project, $ticket, $attributes);
        $integration = $this->createIntegrationFor(IntegrationProvider::TIME_TRACKER, $company);

        $screens_names = ['1_1630667363_1.jpg', '1_1630667363_2.jpg'];
        $this->screenDBCreator($screens_names, $frame);

        //When
        $this->time_tracker_frame_integrator->addActivity($frame);

        //Then
        $this->assertDatabaseHas('time_tracking_activities', [
            'integration_id' => $integration->id,
            'user_id' => $this->user->id,
            'project_id' => $project->id,
            'ticket_id' => $ticket->id,
            'locked_user_id' => null,
            'external_activity_id' => 'tt' . $frame->id,
            'time_tracking_user_id' => null,
            'time_tracking_project_id' => null,
            'time_tracking_note_id' => null,
            'utc_started_at' => Carbon::createFromTimestamp($attributes['from']),
            'utc_finished_at' => Carbon::createFromTimestamp($attributes['to']),
            'tracked' => $tracked_time,
            'activity' => $attributes['activity'],
        ]);
    }

    /**
     * @feature Integration
     * @scenario Save User Activity from Time Tracker
     * @case Long Frame was devided for to activities
     *
     * @test
     */
    public function addActivity_longFrameWasDevided()
    {
        //Given
        $attributes = [
            'from' => Carbon::parse('2022-01-01 10:00:00'),
            'to' => Carbon::parse('2022-01-02 10:00:00'),
            'activity' => 100,
        ];

        $company = $this->createCompany();
        $project = $this->createProject($company);
        $ticket = $this->createTicket();
        $frame = $this->createFrame($project, $ticket, $attributes);
        $integration = $this->createIntegrationFor(IntegrationProvider::TIME_TRACKER, $company);

        $screens_names = ['1_1630667363_1.jpg', '1_1630667363_2.jpg'];
        $this->screenDBCreator($screens_names, $frame);

        //When
        $this->time_tracker_frame_integrator->addActivity($frame);

        //Then
        $this->assertDatabaseCount('time_tracking_activities', 24);
    }
}
