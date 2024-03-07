<?php

namespace Tests\Unit\App\Modules\TimeTracker\Services\TimeTracker;

use App\Models\Db\Company;
use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\Project;
use App\Models\Db\Ticket;
use App\Models\Db\TimeTracker\Frame;
use App\Modules\TimeTracker\DTO\AddFrame;
use App\Modules\TimeTracker\Http\Requests\AddFrames;
use App\Modules\TimeTracker\Services\TimeTracker;
use Carbon\Carbon;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TimeTrackerTest extends TestCase
{
    use DatabaseTransactions;
    use TimeTrackerTrait;

    /**
     * @feature Time Tracker
     * @scenario Add frame
     * @case Check if frame has been properly added
     *
     * @test
     * @covers \App\Modules\TimeTracker\Services\TimeTracker::addFrame
     */
    public function addFrame_add_frame_properly()
    {
        // GIVEN
        $this->createUser();
        $this->be($this->user);

        $from = Carbon::now()->subMinutes(10)->timestamp;
        $to = Carbon::now()->timestamp;
        $frame_dto = $this->createIncomingFrame($from, $to);
        $incoming_frames = $this->createIncomingFrames($frame_dto);

        // WHEN
        $service = app()->make(TimeTracker::class);
        $service->processFrames($incoming_frames);

        // THEN
        $this->assertDatabaseHas('time_tracker_frames', [
            'user_id' => $this->user->id,
            'project_id' => $frame_dto->getProjectId(),
            'ticket_id' => $frame_dto->getTaskId(),
            'activity' => $frame_dto->getActivity(),
        ]);

        $added = Frame::latest()->first();
        $this->assertEquals($frame_dto->getFrom(), $added->from->timestamp);
        $this->assertEquals($frame_dto->getTo(), $added->to->timestamp);
        $this->assertEquals(
            $added->coordinates,
            new Point($frame_dto->getGpsLatitude(), $frame_dto->getGpsLongitude())
        );
        $this->assertEquals($added->screens, $frame_dto->getScreenshots());
    }

    /**
     * @feature Time Tracker
     * @scenario Add frame
     * @case Reject frame, because time in frame longer 16h
     *
     * @test
     * @covers \App\Modules\TimeTracker\Services\TimeTracker::addFrame
     */
    public function addFrame_reject_frame_long_time()
    {
        // GIVEN
        $this->createUser();
        $this->be($this->user);

        $from = Carbon::now()->subDay()->timestamp;
        $to = Carbon::now()->timestamp;
        $frame_dto = $this->createIncomingFrame($from, $to);
        $incoming_frames = $this->createIncomingFrames($frame_dto);

        // WHEN
        $service = app()->make(TimeTracker::class);
        $rejected = $service->processFrames($incoming_frames);

        // THEN
        $this->assertEquals($rejected->getRejectedFrames()[0]->getFrom(), $frame_dto->getFrom());
        $this->assertEquals($rejected->getRejectedFrames()[0]->getTo(), $frame_dto->getTo());
    }

    /**
     * @feature Time Tracker
     * @scenario Add frame
     * @case Frame has Screen in DB, connection success
     *
     * @test
     * @covers \App\Modules\TimeTracker\Services\TimeTracker::addFrame
     */
    public function addFrame_add_frame_screen_was_saved_in_DB_connection()
    {
        // GIVEN
        $this->createUser();
        $this->be($this->user);

        $company = factory(Company::class)->create();
        $project = factory(Project::class)->create();
        $ticket = factory(Ticket::class)->create();

        $screens_names = ['1_1630667363_1.jpg', '1_1630667363_2.jpg'];

        $screens = $this->screenDBCreator($screens_names);

        $frame_dto = new AddFrame([
            'from' => Carbon::now()->subMinutes(10)->timestamp,
            'to' => Carbon::now()->timestamp,
            'companyId' => $company->id,
            'projectId' => $project->id,
            'taskId' => $ticket->id,
            'activity' => 87,
            'screens' => $screens_names,
        ]);
        $frames = $this->createIncomingFrames($frame_dto);

        // WHEN
        $service = app()->make(TimeTracker::class);
        $service->processFrames($frames);

        // THEN
        $this->assertDatabaseHas('time_tracker_frames', [
            'user_id' => $this->user->id,
            'project_id' => $frame_dto->getProjectId(),
            'ticket_id' => $frame_dto->getTaskId(),
            'activity' => $frame_dto->getActivity(),
        ]);
        $added = Frame::latest()->first();

        $this->assertDatabaseHas('time_tracker_activity_frame_screen', [
            'screenable_id' => $added->id,
            'screenable_type' => Frame::class,
            'screen_id' => $screens->first()->id,
        ]);

        $this->assertDatabaseHas('time_tracker_activity_frame_screen', [
            'screenable_id' => $added->id,
            'screenable_type' => Frame::class,
            'screen_id' => $screens->last()->id,
        ]);

        $this->assertDatabaseCount('time_tracker_activity_frame_screen', 2);

        $this->assertEquals($frame_dto->getFrom(), $added->from->timestamp);
        $this->assertEquals($frame_dto->getTo(), $added->to->timestamp);
        $this->assertEquals($added->screens, $frame_dto->getScreenshots());
    }

    /**
     * @feature Time Tracker
     * @scenario Add frame
     * @case Activity has Screen in DB, connection success
     *
     * @test
     * @covers \App\Modules\TimeTracker\Services\TimeTracker::addFrame
     */
    public function addFrame_add_activity_screen_was_saved_in_DB_connection()
    {
        // GIVEN
        $this->createUser();
        $this->be($this->user);

        $company = $this->createCompany();
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $ticket = factory(Ticket::class)->create();
        $integration = $this->createIntegration($company);

        $screens_names = ['1_1630667363_1.jpg', '1_1630667363_2.jpg'];

        $screens = $this->screenDBCreator($screens_names);

        $frame_dto = new AddFrame([
            'from' => Carbon::now()->subMinutes(10)->timestamp,
            'to' => Carbon::now()->timestamp,
            'companyId' => $company->id,
            'projectId' => $project->id,
            'taskId' => $ticket->id,
            'activity' => 87,
            'screens' => $screens_names,
        ]);
        $frames = $this->createIncomingFrames($frame_dto);

        // WHEN
        $service = app()->make(TimeTracker::class);
        $service->processFrames($frames);

        // THEN
        $this->assertDatabaseHas('time_tracker_frames', [
            'user_id' => $this->user->id,
            'project_id' => $frame_dto->getProjectId(),
            'ticket_id' => $frame_dto->getTaskId(),
            'activity' => $frame_dto->getActivity(),
        ]);
        $frame = Frame::latest()->first();
        $activity = Activity::latest()->first();

        $this->assertDatabaseHas('time_tracking_activities', [
            'integration_id' => $integration->id,
            'user_id' => $this->user->id,
            'project_id' => $frame_dto->getProjectId(),
            'ticket_id' => $frame_dto->getTaskId(),
            'activity' => $frame_dto->getActivity(),
        ]);

        $this->assertDatabaseHas('time_tracker_activity_frame_screen', [
            'screenable_id' => $activity->id,
            'screenable_type' => Activity::class,
            'screen_id' => $screens->first()->id,
        ]);

        $this->assertDatabaseHas('time_tracker_activity_frame_screen', [
            'screenable_id' => $activity->id,
            'screenable_type' => Activity::class,
            'screen_id' => $screens->last()->id,
        ]);

        $this->assertDatabaseHas('time_tracker_activity_frame_screen', [
            'screenable_id' => $frame->id,
            'screenable_type' => Frame::class,
            'screen_id' => $screens->first()->id,
        ]);

        $this->assertDatabaseHas('time_tracker_activity_frame_screen', [
            'screenable_id' => $frame->id,
            'screenable_type' => Frame::class,
            'screen_id' => $screens->last()->id,
        ]);

        $this->assertDatabaseCount('time_tracker_activity_frame_screen', 4);
    }

    /**
     * @feature Time Tracker
     * @scenario Add frame
     * @case Frame has Screen in DB, connection fail
     *
     * @test
     * @covers \App\Modules\TimeTracker\Services\TimeTracker::addFrame
     */
    public function addFrame_add_frame_screen_was_saved_in_DB_connection_failed()
    {
        // GIVEN
        $this->createUser();
        $this->be($this->user);

        $company = factory(Company::class)->create();
        $project = factory(Project::class)->create();
        $ticket = factory(Ticket::class)->create();

        $screens_names = ['1_1630667363_1.jpg', '1_1630667363_2.jpg'];

        $frame_dto = new AddFrame([
            'from' => Carbon::now()->subMinutes(10)->timestamp,
            'to' => Carbon::now()->timestamp,
            'companyId' => $company->id,
            'projectId' => $project->id,
            'taskId' => $ticket->id,
            'activity' => 87,
            'screens' => $screens_names,
        ]);
        $frames = $this->createIncomingFrames($frame_dto);

        // WHEN
        $service = app()->make(TimeTracker::class);
        $service->processFrames($frames);

        // THEN
        $this->assertDatabaseHas('time_tracker_frames', [
            'user_id' => $this->user->id,
            'project_id' => $frame_dto->getProjectId(),
            'ticket_id' => $frame_dto->getTaskId(),
            'activity' => $frame_dto->getActivity(),
        ]);
        $added = Frame::latest()->first();

        $this->assertDatabaseCount('time_tracker_activity_frame_screen', 0);

        $this->assertEquals($frame_dto->getFrom(), $added->from->timestamp);
        $this->assertEquals($frame_dto->getTo(), $added->to->timestamp);
        $this->assertEquals($added->screens, $frame_dto->getScreenshots());
    }

    /**
     * @feature Time Tracker
     * @scenario Add frame
     * @case add duplicate frame, frame not seved
     *
     * @test
     * @covers \App\Modules\TimeTracker\Services\TimeTracker::addFrame
     */
    public function addFrame_add_duplicate_frame_in_DB()
    {
        // GIVEN
        $this->createUser();
        $this->be($this->user);

        $company = factory(Company::class)->create();
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $ticket = factory(Ticket::class)->create();
        $this->createIntegration($company);

        $from = Carbon::now()->setHour(8)->subMinutes(10)->getTimestamp();
        $to = Carbon::now()->setHour(8)->getTimestamp();

        $frame = factory(Frame::class)->create([
            'user_id' => $this->user->id,
            'from' => $from,
            'to' => $to,
            'project_id' => $project->id,
            'activity' => 100,
            'ticket_id' => $ticket->id,
        ]);

        factory(Activity::class)->create([
            'user_id' => $this->user->id,
            'project_id' => $project->id,
            'ticket_id' => $ticket->id,
            'utc_started_at' => $from,
            'utc_finished_at' => $to,
            'tracked' => 600,
            'activity' => 100,
        ]);

        $frame_dto = new AddFrame([
            'from' => $from,
            'to' => $to,
            'companyId' => $company->id,
            'projectId' => $project->id,
            'taskId' => $ticket->id,
            'activity' => 100,
        ]);
        $frames = $this->createIncomingFrames($frame_dto);

        // WHEN
        $service = app()->make(TimeTracker::class);
        $service->processFrames($frames);

        // THEN
        $this->assertDatabaseHas('time_tracker_frames', [
            'user_id' => $this->user->id,
            'project_id' => $frame_dto->getProjectId(),
            'ticket_id' => $frame_dto->getTaskId(),
            'activity' => $frame_dto->getActivity(),
        ]);

        $this->assertDatabaseCount('time_tracker_activity_frame_screen', 0);
        $this->assertDatabaseCount('time_tracker_frames', 1);
    }

    /**
     * @feature Time Tracker
     * @scenario Add frame
     * @case Frame has Screen in DB, connection half images given
     *
     * @test
     * @covers \App\Modules\TimeTracker\Services\TimeTracker::addFrame
     */
    public function addFrame_add_frame_screen_was_saved_in_DB_connection_half()
    {
        // GIVEN
        $this->createUser();
        $this->be($this->user);

        $company = factory(Company::class)->create();
        $project = factory(Project::class)->create();
        $ticket = factory(Ticket::class)->create();

        $screens_names = ['1_1630667363_1.jpg', '1_1630667363_2.jpg'];

        $screens = $this->screenDBCreator($screens_names);

        $frame_dto = new AddFrame([
            'from' => Carbon::now()->subMinutes(10)->timestamp,
            'to' => Carbon::now()->timestamp,
            'companyId' => $company->id,
            'projectId' => $project->id,
            'taskId' => $ticket->id,
            'activity' => 87,
            'screens' => ['1_1630667363_1.jpg'],
        ]);
        $frames = $this->createIncomingFrames($frame_dto);

        // WHEN
        $service = app()->make(TimeTracker::class);
        $service->processFrames($frames);

        // THEN
        $this->assertDatabaseHas('time_tracker_frames', [
            'user_id' => $this->user->id,
            'project_id' => $frame_dto->getProjectId(),
            'ticket_id' => $frame_dto->getTaskId(),
            'activity' => $frame_dto->getActivity(),
        ]);
        $added = Frame::latest()->first();

        $this->assertDatabaseHas('time_tracker_activity_frame_screen', [
            'screenable_id' => $added->id,
            'screenable_type' => Frame::class,
            'screen_id' => $screens->first()->id,
        ]);

        $this->assertDatabaseCount('time_tracker_activity_frame_screen', 1);

        $this->assertEquals($frame_dto->getFrom(), $added->from->timestamp);
        $this->assertEquals($frame_dto->getTo(), $added->to->timestamp);
        $this->assertEquals($added->screens, $frame_dto->getScreenshots());
    }

    /**
     * @feature Time Tracker
     * @scenario Add frame
     * @case The Frame with zero period time should not be saved
     *
     * @test
     * @covers \App\Modules\TimeTracker\Services\TimeTracker::addFrame
     */
    public function addFrame_when_zero_period_time_should_not_be_saved()
    {
        // GIVEN
        $this->createUser();
        $this->be($this->user);

        $from = 100;
        $frame_dto = $this->createIncomingFrame($from, $from);
        $incoming_frames = $this->createIncomingFrames($frame_dto);

        // WHEN
        $service = app()->make(TimeTracker::class);
        $service->processFrames($incoming_frames);

        // THEN
        $this->assertSame(0, \DB::table('time_tracker_frames')->whereRaw('1=1')->count());
    }

    /**
     * @feature Time Tracker
     * @scenario Get time summary
     * @case Check if time summary has been properly returned
     *
     * @test
     * @covers \App\Modules\TimeTracker\Services\TimeTracker::getTimeSummary
     */
    public function getTimeSummary_get_data_properly()
    {
        // GIVEN
        $this->createUser();
        $this->be($this->user);

        $date = Carbon::now()->subDay();
        $company = $this->prepareCompany();

        $projects[] = $this->prepareProject($company);
        $projects[] = $this->prepareProject($company);

        $tickets[] = $this->prepareTickets($projects[0]);
        $tickets[] = $this->prepareTickets($projects[1]);

        $this->prepareActivities($projects[0], $tickets[0], $date);
        $this->prepareActivities($projects[1], $tickets[1], $date);

        // WHEN
        /** @var TimeTracker $service */
        $service = app()->make(TimeTracker::class);
        $time_summary = $service->getTimeSummary($date);

        // THEN
        $this->assertCount(4, $time_summary);
//        $this->assertArrayHasKey('projects', $time_summary);
//        $this->assertArrayHasKey('tickets', $time_summary);
//
//        $this->assertCount(1, $time_summary['companies']);
//        $this->assertCount(2, $time_summary['projects']);
//        $this->assertCount(4, $time_summary['tickets']);
//
//        $this->assertSame(18000, $time_summary['companies'][0]['time_summary']);
//        $this->assertSame(9000, $time_summary['projects'][0]['time_summary']);
//        $this->assertSame(9000, $time_summary['projects'][1]['time_summary']);
//        $this->assertSame(1800, $time_summary['tickets'][0]['time_summary']);
//        $this->assertSame(7200, $time_summary['tickets'][1]['time_summary']);
//        $this->assertSame(1800, $time_summary['tickets'][2]['time_summary']);
//        $this->assertSame(7200, $time_summary['tickets'][3]['time_summary']);
    }

    /**
     * @feature Time Tracker
     * @scenario Get time summary
     * @case All activities outside current day
     *
     * @test
     * @dataProvider provideFullOutsideEntryData
     * @covers       \App\Modules\TimeTracker\Services\TimeTracker::getTimeSummary
     */
    public function getTimeSummary_allActivitiesOutsideCurrentDay(
        $started_at,
        $finished_at,
        $date_search,
        $time_zone_offset
    ) {
        // GIVEN
        $this->createUser();
        $this->be($this->user);

        $company = $this->prepareCompany();

        $project = $this->prepareProject($company);
        $ticket = $this->prepareTicket($project);

        $this->prepareActivity(
            $project->id,
            $ticket->id,
            Carbon::parse($started_at),
            Carbon::parse($finished_at)
        );

        // WHEN
        /** @var TimeTracker $service */
        $service = app()->make(TimeTracker::class);
        $time_summary = $service->getTimeSummary(Carbon::parse($date_search), $time_zone_offset);

        // THEN
        $this->assertCount(0, $time_summary);
    }

    /**
     * @feature Time Tracker
     * @scenario Wrap Frame Input
     * @case Check if input data was properly wrapped
     *
     * @test
     * @covers \App\Modules\TimeTracker\Services\TimeTracker::wrapFrameInput
     */
    public function wrapFrameInput_check_if_input_data_was_properly_wrapped()
    {
        // GIVEN
        $input = [
            'from' => Carbon::now()->subMinutes(10)->timestamp,
            'to' => Carbon::now()->timestamp,
            'companyId' => 1,
            'projectId' => 2,
            'taskId' => 3,
            'screens' => [
                'image1_1630667383.jpg',
                'image2_1630667383.jpg',
            ],
            'activity' => 87,
            'gpsPosition' => [
                'latitude' => 52.40780261481211,
                'longitude' => 16.90827594281577,
            ],
        ];
        $service = new AddFrames();
        $service->merge(['frames' => [$input]]);

//         WHEN
        $frames = $service->getFrames();
        $wrapped_data = $frames->current();
        // THEN
        $this->assertInstanceOf(AddFrame::class, $wrapped_data);
        $this->assertSame($wrapped_data->getFrom(), $input['from']);
        $this->assertSame($wrapped_data->getTo(), $input['to']);
        $this->assertSame($wrapped_data->getCompanyId(), $input['companyId']);
        $this->assertSame($wrapped_data->getProjectId(), $input['projectId']);
        $this->assertSame($wrapped_data->getTaskId(), $input['taskId']);
        $this->assertSame($wrapped_data->getActivity(), $input['activity']);
        $this->assertSame($wrapped_data->getGpsLatitude(), $input['gpsPosition']['latitude']);
        $this->assertSame($wrapped_data->getGpsLongitude(), $input['gpsPosition']['longitude']);
        $this->assertSame($wrapped_data->getScreenshots(), $input['screens']);
    }

    /**
     * @param $company
     * @param $project
     * @param $ticket
     *
     * @return AddFrame
     */
    private function createIncomingFrame($from, $to): AddFrame
    {
        $company = factory(Company::class)->create();
        $project = factory(Project::class)->create();
        $ticket = factory(Ticket::class)->create();

        return new AddFrame([
            'from' => $from,
            'to' => $to,
            'companyId' => $company->id,
            'projectId' => $project->id,
            'taskId' => $ticket->id,
            'screens' => [
                'image1_1630667363.jpg',
                'image2_1630667363.jpg',
            ],
            'activity' => 87,
            'gpsPosition' => [
                'latitude' => 52.40780261481211,
                'longitude' => 16.90827594281577,
            ],
        ]);
    }
}
