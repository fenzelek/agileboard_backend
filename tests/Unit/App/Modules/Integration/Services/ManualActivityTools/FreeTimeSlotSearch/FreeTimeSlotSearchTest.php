<?php

namespace Tests\Unit\App\Modules\Integration\Services\ManualActivityTools\FreeTimeSlotSearch;

use App\Models\Db\Company;
use App\Models\Db\Project;
use App\Models\Db\Ticket;
use App\Models\Other\RoleType;
use App\Modules\Integration\Services\ManualActivityTools\FreeTimeSlotSearch;
use App\Modules\Integration\Services\ManualActivityTools\ManualActivityTimeConverter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Unit\App\Modules\Integration\Services\ManualActivityTools\ManualActivityToolsTrait;

class FreeTimeSlotSearchTest extends TestCase
{
    use DatabaseTransactions;
    use ManualActivityToolsTrait;

    /**
     * @var ManualActivityTimeConverter
     */
    private ManualActivityTimeConverter $manual_converter;
    private Project $project;
    private Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $this->company = factory(Company::class)->create();
        $this->project = $this->getProject($this->company, $this->user, RoleType::DEVELOPER);
        $this->ticket = $this->getTicket($this->project);

        $this->free_time_search = $this->app->make(FreeTimeSlotSearch::class);
    }

    /**
     * @feature Time Tracker
     * @scenario Manual activity add
     * @case success convert DB previously deleted
     *
     * @test
     * #0
     */
    public function lookup_activities_DB_is_empty_but_previously_deleted()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('2021-10-01 11:00:00', '2021-10-01 11:10:00');
        $activity =
            $this->createDBActivity(
                $this->user,
                '2021-10-01 11:00:00',
                '2021-10-01 11:10:00',
                $this->project,
                $this->ticket
            );
        $activity->delete();

        //WHEN
        $response = $this->free_time_search->lookup($store_activity);

        //THEN
        $this->assertCount(1, $response);
        $this->assertEquals($response[0]->from, '2021-10-01 11:00:00');
        $this->assertEquals($response[0]->to, '2021-10-01 11:10:00');
    }

    /**
     * @feature Time Tracker
     * @scenario Manual activity tools
     * @case success convert DB has not activities, new activity can be saved
     *
     * @test
     * #1
     */
    public function lookup_activities_DB_free()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('2021-10-01 11:00:00', '2021-10-01 11:10:00');

        //WHEN
        $response = $this->free_time_search->lookup($store_activity);

        //THEN
        $this->assertCount(1, $response);
        $this->assertEquals($response[0]->from, '2021-10-01 11:00:00');
        $this->assertEquals($response[0]->to, '2021-10-01 11:10:00');
    }

    /**
     * @feature Time Tracker
     * @scenario Manual activity add
     * @case failed convert DB has same activity
     *
     * @test
     * #2
     */
    public function lookup_activities_DB_is_not_empty()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('2021-10-01 11:00:00', '2021-10-01 11:10:00');
        $this->createDBActivity(
            $this->user,
            '2021-10-01 11:00:00',
            '2021-10-01 11:10:00',
            $this->project,
            $this->ticket
        );

        //WHEN
        $response = $this->free_time_search->lookup($store_activity);

        //THEN
        $this->assertCount(0, $response);
    }

    /**
     * @feature Time Tracker
     * @scenario Manual activity tools
     * @case success convert, DB has many activities, end time free
     *
     * @test
     * #3
     */
    public function lookup_activities_DB_is_not_empty_many_activities_tail_free()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('2021-10-01 11:00:00', '2021-10-01 11:10:00');
        $this->createDBActivity(
            $this->user,
            '2021-10-01 10:55:00',
            '2021-10-01 11:05:00',
            $this->project,
            $this->ticket
        );
        $this->createDBActivity(
            $this->user,
            '2021-10-01 11:05:00',
            '2021-10-01 11:08:00',
            $this->project,
            $this->ticket
        );

        //WHEN
        $response = $this->free_time_search->lookup($store_activity);

        //THEN
        $this->assertCount(1, $response);
        $this->assertEquals($response[0]->from, '2021-10-01 11:08:00');
        $this->assertEquals($response[0]->to, '2021-10-01 11:10:00');
    }

    /**
     * @feature Time Tracker
     * @scenario Manual activity tools
     * @case success convert, DB has many activities, start time free
     *
     * @test
     * #4
     */
    public function lookup_activities_DB_is_not_empty_many_activities_head_free()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('2021-10-01 11:00:00', '2021-10-01 11:10:00');
        $this->createDBActivity(
            $this->user,
            '2021-10-01 11:02:00',
            '2021-10-01 11:05:00',
            $this->project,
            $this->ticket
        );
        $this->createDBActivity(
            $this->user,
            '2021-10-01 11:05:00',
            '2021-10-01 11:15:00',
            $this->project,
            $this->ticket
        );

        //WHEN
        $response = $this->free_time_search->lookup($store_activity);

        //THEN
        $this->assertCount(1, $response);
        $this->assertEquals($response[0]->from, '2021-10-01 11:00:00');
        $this->assertEquals($response[0]->to, '2021-10-01 11:02:00');
    }

    /**
     * @feature Time Tracker
     * @scenario Manual activity tools
     * @case failed convert DB has longer activity
     *
     * @test
     * #5
     */
    public function lookup_activities_DB_has_longer()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('2021-10-01 11:00:00', '2021-10-01 11:10:00');
        $this->createDBActivity(
            $this->user,
            '2021-10-01 10:58:00',
            '2021-10-01 11:12:00',
            $this->project,
            $this->ticket
        );

        //WHEN
        $response = $this->free_time_search->lookup($store_activity);

        //THEN
        $this->assertCount(0, $response);
    }

    /**
     * @feature Time Tracker
     * @scenario Manual activity tools
     * @case success convert DB has activity, set free slots
     *
     * @test
     * #6
     */
    public function lookup_activities_two_new_activity_set_free_slots()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('2021-10-01 11:00:00', '2021-10-01 11:10:00');
        $this->createDBActivity(
            $this->user,
            '2021-10-01 10:55:00',
            '2021-10-01 11:02:00',
            $this->project,
            $this->ticket
        );
        $this->createDBActivity(
            $this->user,
            '2021-10-01 11:04:00',
            '2021-10-01 11:06:00',
            $this->project,
            $this->ticket
        );
        $this->createDBActivity(
            $this->user,
            '2021-10-01 11:08:00',
            '2021-10-01 11:15:00',
            $this->project,
            $this->ticket
        );

        //WHEN
        $response = $this->free_time_search->lookup($store_activity);

        //THEN
        $this->assertCount(2, $response);

        $this->assertEquals($response[0]->from, '2021-10-01 11:02:00');
        $this->assertEquals($response[0]->to, '2021-10-01 11:04:00');

        $this->assertEquals($response[1]->from, '2021-10-01 11:06:00');
        $this->assertEquals($response[1]->to, '2021-10-01 11:08:00');
    }

    /**
     * @feature Time Tracker
     * @scenario Manual activity tools
     * @case success convert DB has activity, new slots head and tail
     *
     * @test
     * #7
     */
    public function lookup_activities_set_fre_slots_head_and_tail()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('2021-10-01 11:00:00', '2021-10-01 11:10:00');
        $this->createDBActivity(
            $this->user,
            '2021-10-01 11:02:00',
            '2021-10-01 11:04:00',
            $this->project,
            $this->ticket
        );
        $this->createDBActivity(
            $this->user,
            '2021-10-01 11:06:00',
            '2021-10-01 11:08:00',
            $this->project,
            $this->ticket
        );
        $history = 1;

        //WHEN
        $response = $this->free_time_search->lookup($store_activity);

        //THEN
        $this->assertCount(3, $response);

        $this->assertEquals($response[0]->from, '2021-10-01 11:00:00');
        $this->assertEquals($response[0]->to, '2021-10-01 11:02:00');

        $this->assertEquals($response[1]->from, '2021-10-01 11:04:00');
        $this->assertEquals($response[1]->to, '2021-10-01 11:06:00');

        $this->assertEquals($response[2]->from, '2021-10-01 11:08:00');
        $this->assertEquals($response[2]->to, '2021-10-01 11:10:00');
    }

    /**
     * @feature Time Tracker
     * @scenario Manual activity tools
     * @case success convert DB has activity, old activity in tail
     *
     * @test
     * #8
     */
    public function lookup_activities_old_activity_in_tail()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('2021-10-01 11:00:00', '2021-10-01 11:10:00');

        $this->createDBActivity(
            $this->user,
            '2021-10-01 11:08:00',
            '2021-10-01 11:15:00',
            $this->project,
            $this->ticket
        );

        //WHEN
        $response = $this->free_time_search->lookup($store_activity);

        //THEN
        $this->assertCount(1, $response);

        $this->assertEquals($response[0]->from, '2021-10-01 11:00:00');
        $this->assertEquals($response[0]->to, '2021-10-01 11:08:00');
    }

    /**
     * @feature Time Tracker
     * @scenario Manual activity tools
     * @case success convert DB has activity, old activity in tail
     *
     * @test
     * #9
     */
    public function lookup_activities_old_activity_in_head()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('2021-10-01 11:00:00', '2021-10-01 11:10:00');
        $this->createDBActivity(
            $this->user,
            '2021-10-01 10:58:00',
            '2021-10-01 11:05:00',
            $this->project,
            $this->ticket
        );

        //WHEN
        $response = $this->free_time_search->lookup($store_activity);

        //THEN
        $this->assertCount(1, $response);
        $this->assertEquals($response[0]->from, '2021-10-01 11:05:00');
        $this->assertEquals($response[0]->to, '2021-10-01 11:10:00');
    }

    /**
     * @feature Time Tracker
     * @scenario Manual activity tools
     * @case success convert DB, old activity half from head new manual activity
     *
     * @test
     * #10
     */
    public function lookup_activities_old_activity_half_from_head()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('2021-10-01 11:00:00', '2021-10-01 11:10:00');
        $this->createDBActivity(
            $this->user,
            '2021-10-01 11:00:00',
            '2021-10-01 11:05:00',
            $this->project,
            $this->ticket
        );

        //WHEN
        $response = $this->free_time_search->lookup($store_activity);

        //THEN
        $this->assertCount(1, $response);

        $this->assertEquals($response[0]->from, '2021-10-01 11:05:00');
        $this->assertEquals($response[0]->to, '2021-10-01 11:10:00');
    }

    /**
     * @feature Time Tracker
     * @scenario Manual activity tools
     * @case success convert DB, old activity half from tail new manual activity
     *
     * @test
     * #11
     */
    public function lookup_activities_old_activity_half_from_tail()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('2021-10-01 11:00:00', '2021-10-01 11:10:00');
        $this->createDBActivity(
            $this->user,
            '2021-10-01 11:05:00',
            '2021-10-01 11:10:00',
            $this->project,
            $this->ticket
        );

        //WHEN
        $response = $this->free_time_search->lookup($store_activity);

        //THEN
        $this->assertCount(1, $response);

        $this->assertEquals($response[0]->from, '2021-10-01 11:00:00');
        $this->assertEquals($response[0]->to, '2021-10-01 11:05:00');
    }

    /**
     * @feature Time Tracker
     * @scenario Manual activity tools
     * @case failed convert DB, old activity full time even more
     *
     * @test
     * #12
     */
    public function lookup_activities_three_old_activities_longer()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('2021-10-01 11:00:00', '2021-10-01 11:10:00');
        $this->createDBActivity(
            $this->user,
            '2021-10-01 10:55:00',
            '2021-10-01 11:02:00',
            $this->project,
            $this->ticket
        );
        $this->createDBActivity(
            $this->user,
            '2021-10-01 11:02:00',
            '2021-10-01 11:08:00',
            $this->project,
            $this->ticket
        );
        $this->createDBActivity(
            $this->user,
            '2021-10-01 11:08:00',
            '2021-10-01 11:15:00',
            $this->project,
            $this->ticket
        );

        //WHEN
        $response = $this->free_time_search->lookup($store_activity);

        //THEN
        $this->assertCount(0, $response);
    }

    /**
     * @feature Time Tracker
     * @scenario Manual activity tools
     * @case failed convert DB, old activity full time even more
     *
     * @test
     * #13
     */
    public function lookup_activities_two_old_activities_longer()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('2021-10-01 11:00:00', '2021-10-01 11:10:00');
        $this->createDBActivity(
            $this->user,
            '2021-10-01 10:55:00',
            '2021-10-01 11:05:00',
            $this->project,
            $this->ticket
        );
        $this->createDBActivity(
            $this->user,
            '2021-10-01 11:05:00',
            '2021-10-01 11:15:00',
            $this->project,
            $this->ticket
        );

        //WHEN
        $response = $this->free_time_search->lookup($store_activity);

        //THEN
        $this->assertCount(0, $response);
    }

    /**
     * @feature Time Tracker
     * @scenario Manual activity tools
     * @case success convert DB, old activity not full time, middle free
     *
     * @test
     * #14
     */
    public function lookup_activities_two_old_activities_longer_middle_free()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('2021-10-01 11:00:00', '2021-10-01 11:10:00');
        $this->createDBActivity(
            $this->user,
            '2021-10-01 10:55:00',
            '2021-10-01 11:04:00',
            $this->project,
            $this->ticket
        );
        $this->createDBActivity(
            $this->user,
            '2021-10-01 11:06:00',
            '2021-10-01 11:15:00',
            $this->project,
            $this->ticket
        );

        //WHEN
        $response = $this->free_time_search->lookup($store_activity);

        //THEN
        $this->assertCount(1, $response);

        $this->assertEquals($response[0]->from, '2021-10-01 11:04:00');
        $this->assertEquals($response[0]->to, '2021-10-01 11:06:00');
    }

    /**
     * @feature Time Tracker
     * @scenario Manual activity tools
     * @case success convert DB, old activity not full time, left side longer
     *
     * @test
     * #15
     */
    public function lookup_activities_two_old_activities_left_side_longer_middle_free()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('2021-10-01 11:00:00', '2021-10-01 11:10:00');
        $this->createDBActivity(
            $this->user,
            '2021-10-01 10:55:00',
            '2021-10-01 11:02:00',
            $this->project,
            $this->ticket
        );
        $this->createDBActivity(
            $this->user,
            '2021-10-01 11:05:00',
            '2021-10-01 11:08:00',
            $this->project,
            $this->ticket
        );

        //WHEN
        $response = $this->free_time_search->lookup($store_activity);

        //THEN
        $this->assertCount(2, $response);

        $this->assertEquals($response[0]->from, '2021-10-01 11:02:00');
        $this->assertEquals($response[0]->to, '2021-10-01 11:05:00');
        $this->assertEquals($response[1]->from, '2021-10-01 11:08:00');
        $this->assertEquals($response[1]->to, '2021-10-01 11:10:00');
    }

    /**
     * @feature Time Tracker
     * @scenario Manual activity tools
     * @case success convert DB, old activity not full time, right side longer
     *
     * @test
     * #16
     */
    public function lookup_activities_two_old_activities_right_side_longer_middle_free()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('2021-10-01 11:00:00', '2021-10-01 11:10:00');
        $this->createDBActivity(
            $this->user,
            '2021-10-01 11:02:00',
            '2021-10-01 11:05:00',
            $this->project,
            $this->ticket
        );
        $this->createDBActivity(
            $this->user,
            '2021-10-01 11:08:00',
            '2021-10-01 11:15:00',
            $this->project,
            $this->ticket
        );

        //WHEN
        $response = $this->free_time_search->lookup($store_activity);

        //THEN
        $this->assertCount(2, $response);

        $this->assertEquals($response[0]->from, '2021-10-01 11:00:00');
        $this->assertEquals($response[0]->to, '2021-10-01 11:02:00');
        $this->assertEquals($response[1]->from, '2021-10-01 11:05:00');
        $this->assertEquals($response[1]->to, '2021-10-01 11:08:00');
    }

    /**
     * @feature Time Tracker
     * @scenario Manual activity tools
     * @case success convert DB, old activity not full time, many activities, head and tail longer
     *
     * @test
     * #17
     */
    public function lookup_activities_many_old_activities_head_tail_longer()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('2021-10-01 11:00:00', '2021-10-01 12:00:00');
        $this->createDBActivity(
            $this->user,
            '2021-10-01 10:55:00',
            '2021-10-01 11:05:00',
            $this->project,
            $this->ticket
        );
        $this->createDBActivity(
            $this->user,
            '2021-10-01 11:08:00',
            '2021-10-01 11:15:00',
            $this->project,
            $this->ticket
        );
        $this->createDBActivity(
            $this->user,
            '2021-10-01 11:20:00',
            '2021-10-01 11:30:00',
            $this->project,
            $this->ticket
        );
        $this->createDBActivity(
            $this->user,
            '2021-10-01 11:35:00',
            '2021-10-01 11:50:00',
            $this->project,
            $this->ticket
        );
        $this->createDBActivity(
            $this->user,
            '2021-10-01 11:55:00',
            '2021-10-01 12:10:00',
            $this->project,
            $this->ticket
        );

        //WHEN
        $response = $this->free_time_search->lookup($store_activity);

        //THEN
        $this->assertCount(4, $response);

        $this->assertEquals($response[0]->from, '2021-10-01 11:05:00');
        $this->assertEquals($response[0]->to, '2021-10-01 11:08:00');
        $this->assertEquals($response[1]->from, '2021-10-01 11:15:00');
        $this->assertEquals($response[1]->to, '2021-10-01 11:20:00');
        $this->assertEquals($response[2]->from, '2021-10-01 11:30:00');
        $this->assertEquals($response[2]->to, '2021-10-01 11:35:00');
        $this->assertEquals($response[3]->from, '2021-10-01 11:50:00');
        $this->assertEquals($response[3]->to, '2021-10-01 11:55:00');
    }

    /**
     * @feature Time Tracker
     * @scenario Manual activity tools
     * @case success convert DB, old activity not full time, many activities, head and tail shorter
     *
     * @test
     * #17
     */
    public function lookup_activities_many_old_activities_head_tail_shorter()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('2021-10-01 11:00:00', '2021-10-01 12:00:00');
        $this->createDBActivity(
            $this->user,
            '2021-10-01 11:08:00',
            '2021-10-01 11:15:00',
            $this->project,
            $this->ticket
        );
        $this->createDBActivity(
            $this->user,
            '2021-10-01 11:20:00',
            '2021-10-01 11:30:00',
            $this->project,
            $this->ticket
        );
        $this->createDBActivity(
            $this->user,
            '2021-10-01 11:35:00',
            '2021-10-01 11:50:00',
            $this->project,
            $this->ticket
        );

        //WHEN
        $response = $this->free_time_search->lookup($store_activity);

        //THEN
        $this->assertCount(4, $response);

        $this->assertEquals($response[0]->from, '2021-10-01 11:00:00');
        $this->assertEquals($response[0]->to, '2021-10-01 11:08:00');
        $this->assertEquals($response[1]->from, '2021-10-01 11:15:00');
        $this->assertEquals($response[1]->to, '2021-10-01 11:20:00');
        $this->assertEquals($response[2]->from, '2021-10-01 11:30:00');
        $this->assertEquals($response[2]->to, '2021-10-01 11:35:00');
        $this->assertEquals($response[3]->from, '2021-10-01 11:50:00');
        $this->assertEquals($response[3]->to, '2021-10-01 12:00:00');
    }

    /**
     * @feature Time Tracker
     * @scenario Manual activity tools
     * @case success convert DB, old activity not full time, left side longer
     *
     * @test
     * #18
     */
    public function lookup_activities_three_old_activities_left_side_longer_middle_free()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('2021-10-01 11:00:00', '2021-10-01 12:00:00');
        $this->createDBActivity(
            $this->user,
            '2021-10-01 10:50:00',
            '2021-10-01 11:05:00',
            $this->project,
            $this->ticket
        );
        $this->createDBActivity(
            $this->user,
            '2021-10-01 11:05:00',
            '2021-10-01 11:15:00',
            $this->project,
            $this->ticket
        );
        $this->createDBActivity(
            $this->user,
            '2021-10-01 11:20:00',
            '2021-10-01 11:30:00',
            $this->project,
            $this->ticket
        );

        //WHEN
        $response = $this->free_time_search->lookup($store_activity);

        //THEN
        $this->assertCount(2, $response);

        $this->assertEquals($response[0]->from, '2021-10-01 11:15:00');
        $this->assertEquals($response[0]->to, '2021-10-01 11:20:00');
        $this->assertEquals($response[1]->from, '2021-10-01 11:30:00');
        $this->assertEquals($response[1]->to, '2021-10-01 12:00:00');
    }

    /**
     * @feature Time Tracker
     * @scenario Manual activity tools
     * @case success convert DB, old activity not full time, right side longer
     *
     * @test
     * #18
     */
    public function lookup_activities_three_old_activities_right_side_longer_middle_free()
    {
        //GIVEN
        $store_activity = $this->getStoreActivity('2021-10-01 11:00:00', '2021-10-01 12:00:00');
        $this->createDBActivity(
            $this->user,
            '2021-10-01 11:10:00',
            '2021-10-01 11:20:00',
            $this->project,
            $this->ticket
        );
        $this->createDBActivity(
            $this->user,
            '2021-10-01 11:30:00',
            '2021-10-01 11:40:00',
            $this->project,
            $this->ticket
        );
        $this->createDBActivity(
            $this->user,
            '2021-10-01 11:40:00',
            '2021-10-01 12:10:00',
            $this->project,
            $this->ticket
        );

        //WHEN
        $response = $this->free_time_search->lookup($store_activity);

        //THEN
        $this->assertCount(2, $response);

        $this->assertEquals($response[0]->from, '2021-10-01 11:00:00');
        $this->assertEquals($response[0]->to, '2021-10-01 11:10:00');
        $this->assertEquals($response[1]->from, '2021-10-01 11:20:00');
        $this->assertEquals($response[1]->to, '2021-10-01 11:30:00');
    }
}
