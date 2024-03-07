<?php

namespace Tests\Unit\App\Modules\Integration\Services\ActivityTools\ActivitySplitter;

use App\Models\Db\Integration\TimeTracking\Activity;
use App\Modules\Integration\Services\ActivityTools\ActivitySplitter;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ActivitySplitterTest extends TestCase
{
    use DatabaseTransactions;
    use ActivitySplitterTrait;

    /**
     * @var ActivitySplitter
     */
    private $activity_splitter;

    public function setUp(): void
    {
        parent::setUp();
        $this->activity_splitter = $this->app->make(ActivitySplitter::class);
    }

    /**
     * @feature Time Tracker
     * @scenario Add Activity
     * @case success
     *
     * @test
     * @Expectation Divide multihours frame
     * @dataProvider provideActivityTimestamps
     */
    public function success_generate_paths_for_save($utc_started_at, $utc_finished_at, $expected_frames_count)
    {
        //GIVEN
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompany();
        $project = $this->createProject($company);
        $ticket = $this->createTicket();

        $activity = $this->createActivity(
            $project,
            $ticket,
            $this->user,
            compact('utc_started_at', 'utc_finished_at')
        );

        //WHEN
        $splitted_activities = $this->activity_splitter->split($activity);

        //THEN
        $this->assertCount($expected_frames_count, $splitted_activities);
    }

    /**
     * @feature Time Tracker
     * @scenario Add Activity
     * @case success
     *
     * @test
     * @Expectation Non Divide single hour Activity
     */
    public function success_whenInboundHour_ActivityNotModified()
    {
        //GIVEN
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $company = $this->createCompany();
        $project = $this->createProject($company);
        $ticket = $this->createTicket();

        $utc_started_at = '2022-01-01 10:50:00';
        $utc_finished_at = '2022-01-01 10:59:59';
        $activity = 599;

        $created_activity =
            $this->createActivity(
                $project,
                $ticket,
                $this->user,
                compact('utc_started_at', 'utc_finished_at', 'activity')
            );

        //WHEN
        $splitted_activities = $this->activity_splitter->split($created_activity);

        //THEN
        $this->assertCount(1, $splitted_activities);
        $this->assertEquals($splitted_activities[0]->utc_started_at, $utc_started_at);
        $this->assertEquals($splitted_activities[0]->utc_finished_at, $utc_finished_at);
        $this->assertSame($splitted_activities[0]->activity, $activity);
    }

    /**
     * @feature Time Tracker
     * @scenario Add Activity
     * @case success
     *
     * @test
     * @Expectation Modify multi-hour frame
     */
    public function success_whenMultiHour_ActivityWasSplit()
    {
        //GIVEN
        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $company = $this->createCompany();
        $project = $this->createProject($company);
        $ticket = $this->createTicket();

        $utc_started_at = '2022-01-01 10:30:00';
        $utc_finished_at = '2022-01-01 13:30:00';
        $activity = 60 * 60 * 3;

        $frame =
            $this->createActivity(
                $project,
                $ticket,
                $this->user,
                compact('utc_started_at', 'utc_finished_at', 'activity')
            );

        //WHEN
        $splitted_activities = $this->activity_splitter->split($frame);

        //THEN
        $this->assertCount(4, $splitted_activities);

        $this->assertEquals($utc_started_at, $splitted_activities[0]->utc_started_at);

        $this->assertEquals(
            Carbon::parse($utc_started_at)->addHour()->startOfHour()->format('Y-m-d H-i-s'),
            $splitted_activities[0]->utc_finished_at->format('Y-m-d H-i-s')
        );

        $this->assertEquals(30 * 60, $splitted_activities[0]->activity);

        $this->assertEquals(
            Carbon::parse($utc_finished_at)->startOfHour()->format('Y-m-d H-i-s'),
            $splitted_activities[3]->utc_started_at->format('Y-m-d H-i-s')
        );

        $this->assertEquals(
            $utc_finished_at,
            $splitted_activities[3]->utc_finished_at->format('Y-m-d H:i:s')
        );

        $this->assertEquals(60 * 60, $splitted_activities[1]->activity);

        $this->assertDatabaseHas(Activity::class, [
            'activity' => 30 * 60,
            'tracked' => 1800,
            'utc_started_at' => $utc_started_at,
            'utc_finished_at' => '2022-01-01 11-00-00',
        ]);

        $this->assertDatabaseHas(Activity::class, [
            'activity' => 60 * 60,
            'tracked' => 3600,
            'utc_started_at' => '2022-01-01 11-00-00',
            'utc_finished_at' => '2022-01-01 12-00-00',
        ]);

        $this->assertDatabaseHas(Activity::class, [
            'activity' => 60 * 60,
            'tracked' => 3600,
            'utc_started_at' => '2022-01-01 12-00-00',
            'utc_finished_at' => '2022-01-01 13-00-00',
        ]);

        $this->assertDatabaseHas(Activity::class, [
            'activity' => 30 * 60,
            'tracked' => 1800,
            'utc_started_at' => '2022-01-01 13-00-00',
            'utc_finished_at' => '2022-01-01 13-30-00',
        ]);
    }
}
