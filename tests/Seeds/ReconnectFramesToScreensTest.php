<?php

namespace Tests\Seeds;

use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\Ticket;
use App\Models\Db\TimeTracker\Frame;
use App\Models\Db\TimeTracker\Screen;
use App\Models\Other\RoleType;
use DatabaseSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use ReconnectFramesToScreensSeed;
use Tests\Helpers\ProjectHelper;
use Tests\TestCase;

class ReconnectFramesToScreensTest extends TestCase
{
    use DatabaseTransactions;
    use ProjectHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $this->cpmpany = $this->createCompanyWithRole(RoleType::OWNER);
        $this->project = $this->getProject($this->cpmpany, $this->user->id, RoleType::OWNER);
        $this->ticket = $this->createTicket();
    }

    /**
     * @feature Time Tracker
     * @scenario Frame Screen Relation
     * @case Reconnect frame to screen
     *
     * @test
     */
    public function success_frame_to_screen()
    {
        //GIVEN
        $screens_names = ['1_1630667363_1.jpg', '1_1630667363_2.jpg', '1_1630667363_3.jpg'];
        $this->createScreenshots($screens_names);
        $frame_one = $this->makeDbFrame(600, $screens_names);

        $this->createDBActivity(600, $frame_one->id);

        $screens_names = ['undefined_1630667363_1.jpg', 'undefined_1630667363_2.jpg', 'undefined_1630667363_3.jpg'];
        $this->createScreenshots($screens_names);
        $frame_two = $this->makeDbFrame(600, $screens_names);

        $this->createDBActivity(600, $frame_two->id);

        //WHEN
        app(DatabaseSeeder::class)->call(ReconnectFramesToScreensSeed::class);
        //THEN

        $this->assertDatabaseCount('time_tracker_activity_frame_screen', 12);
        $this->assertDatabaseCount('time_tracker_screens', 6);
    }

    /**
     * @feature Time Tracker
     * @scenario Frame Screen Relation
     * @case Reconnect frame to screen, but not all reconnect
     *
     * @test
     */
    public function success_frame_to_screen_but_not_all_reconnect()
    {
        //GIVEN
        $screens_names = ['1_1630667363_1.jpg', '1_1630667363_2.jpg', '1_1630667363_3.jpg'];
        $this->createScreenshots($screens_names);
        $frame_one = $this->makeDbFrame(600, $screens_names);

        $this->createDBActivity(600, $frame_one->id);

        $screens_names = ['undefined_1630667363_1.jpg', 'undefined_1630667363_2.jpg', 'undefined_1630667363_3.jpg'];
        $this->createScreenshots($screens_names);
        array_pop($screens_names);
        $frame_two = $this->makeDbFrame(600, $screens_names);

        $this->createDBActivity(600, $frame_two->id);

        //WHEN
        app(DatabaseSeeder::class)->call(ReconnectFramesToScreensSeed::class);
        //THEN

        $this->assertDatabaseCount('time_tracker_activity_frame_screen', 10);
        $this->assertDatabaseCount('time_tracker_screens', 6);
    }

    /**
     * @feature Time Tracker
     * @scenario Frame Screen Relation
     * @case Reconnect frame to screen, but not all reconnect, wrong name
     *
     * @test
     */
    public function success_frame_to_screen_but_not_all_reconnect_some_name_wrong()
    {
        //GIVEN
        $screens_names = ['1_1630667363_1.jpg', '1_1630667363_2.jpg', '1_1630667363_3.jpg'];
        $this->createScreenshots($screens_names);
        $frame_one = $this->makeDbFrame(600, $screens_names);

        $this->createDBActivity(600, $frame_one->id);

        $screens_names = ['undefined_1630667363_1.jpg', 'undefined_1630667363_2.jpg', 'undefined_1630667363_3.jpg'];
        $this->createScreenshots($screens_names);
        $wrong_names = ['_1630667363_1.jpg', '_1630667363_2.jpg', '_1630667363_3.jpg'];
        $frame_two = $this->makeDbFrame(600, $wrong_names);

        $this->createDBActivity(600, $frame_two->id);

        //WHEN
        app(DatabaseSeeder::class)->call(ReconnectFramesToScreensSeed::class);
        //THEN

        $this->assertDatabaseCount('time_tracker_activity_frame_screen', 6);
        $this->assertDatabaseCount('time_tracker_screens', 6);
    }

    /**
     * @feature Time Tracker
     * @scenario Frame Screen Relation
     * @case one activity found
     *
     * @test
     */
    public function search_activity_frame_relation_activity_found_only_one_activity()
    {
        //GIVEN
        $screens_names_one = ['1_1630667363_1.jpg', '1_1630667363_2.jpg', '1_1630667363_3.jpg'];
        $this->createScreenshots($screens_names_one);
        $frame_one = $this->makeDbFrame(600, $screens_names_one);

        $this->createDBActivity(600, $frame_one->id);

        $screens_names_two = ['2_1630667363_1.jpg', '2_1630667363_2.jpg', '2_1630667363_3.jpg'];
        $this->createScreenshots($screens_names_two);
        $this->makeDbFrame(600, $screens_names_two);

        //WHEN
        app(DatabaseSeeder::class)->call(ReconnectFramesToScreensSeed::class);
        //THEN

        $this->assertDatabaseCount('time_tracker_activity_frame_screen', 9);
        $this->assertDatabaseCount('time_tracker_screens', 6);
    }

    /**
     * @feature Time Tracker
     * @scenario Frame Screen Relation
     * @case activity not found
     *
     * @test
     */
    public function search_activity_frame_relation_activity_not_found()
    {
        //GIVEN
        $screens_names = ['1_1630667363_1.jpg', '1_1630667363_2.jpg', '1_1630667363_3.jpg'];
        $this->createScreenshots($screens_names);
        $this->makeDbFrame(600, $screens_names);

        //WHEN
        app(DatabaseSeeder::class)->call(ReconnectFramesToScreensSeed::class);
        //THEN

        $this->assertDatabaseCount('time_tracker_activity_frame_screen', 3);
        $this->assertDatabaseCount('time_tracker_screens', 3);
    }

    public function createDBActivity(int $tracked_time, $frame_id): Activity
    {
        return factory(Activity::class)->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'ticket_id' => $this->ticket->id,
            'external_activity_id' => $frame_id,
            'utc_started_at' => 1600000000,
            'utc_finished_at' => 1600000000 + $tracked_time,
            'tracked' => $tracked_time,
            'activity' => 100,
        ]);
    }

    protected function createScreenshots($names): void
    {
        foreach ($names as $name) {
            /**
             * @var Screen $screen
             */
            $screen = factory(Screen::class)->make([
                'name' => $name,
            ]);
            $screen->user()->associate($this->user);
            $screen->save();
        }
    }

    private function makeDbFrame($tracked_time, $screens_names)
    {
        return factory(Frame::class)->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'ticket_id' => $this->ticket->id,
            'from' => 1600000000,
            'to' => 1600000000 + $tracked_time,
            'activity' => 100,
            'screens' => $screens_names,
        ]);
    }

    private function createTicket(): Ticket
    {
        return factory(Ticket::class)->create();
    }
}
