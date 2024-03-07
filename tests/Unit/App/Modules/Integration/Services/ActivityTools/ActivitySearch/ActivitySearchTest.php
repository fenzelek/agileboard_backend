<?php

namespace Tests\Unit\App\Modules\Integration\Services\ActivityTools\ActivitySearch;

use App\Models\Db\Integration\Integration;
use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\User;
use App\Modules\Integration\Services\ActivityTools\ActivitySearch;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use function factory;

class ActivitySearchTest extends TestCase
{
    use DatabaseTransactions;
    /**
     * @var ActivitySearch
     */
    private $activity_search;
    /**
     * @var User
     */
    private $own;
    /**
     * @var User
     */
    private $other_user;

    public function setUp():void
    {
        parent::setUp();
        $this->activity_search = $this->app->make(ActivitySearch::class);
        $this->other_user = factory(User::class)->create();
        $this->own = factory(User::class)->create();
    }

    /**
     * @feature Integration
     * @scenario Save User Activity from Time Tracker
     * @case Existing activity not found during adding User Activity
     *
     * @test
     */
    public function lookupOverlap_existing_not_found()
    {
        //Given
        $incoming_started_at = Carbon::parse('2020-01-01 11:10:00');
        $incoming_finished_at = Carbon::parse('2020-01-01 14:10:00');
        $activity = $this->makeActivity($incoming_started_at, $incoming_finished_at);

        //When
        $overlaps = $this->activity_search->lookupOverLap($activity);

        //Then
        $this->assertCount(0, $overlaps);
    }

    /**
     * @feature Integration
     * @scenario Save User Activity from Time Tracker
     * @case Existing activity not found during adding User Activity
     *
     * @test
     */
    public function lookupOverlap_skip_other_user_activities()
    {
        //Given
        $incoming_started_at = Carbon::parse('2020-01-01 11:10:00');
        $incoming_finished_at = Carbon::parse('2020-01-01 14:10:00');
        $this->createOtherActivity($incoming_started_at, $incoming_finished_at);
        $activity = $this->makeActivity($incoming_started_at, $incoming_finished_at);

        //When
        $overlaps = $this->activity_search->lookupOverLap($activity);

        //Then
        $this->assertCount(0, $overlaps);
    }

    /**
     * @feature Integration
     * @scenario Save User Activity from Time Tracker
     * @case Existing activity found during adding User Activity
     *
     * @test
     */
    public function lookupOverlap_overlap_activity_found()
    {
        //Given
        $incoming_started_at = Carbon::parse('2020-01-01 11:10:00');
        $incoming_finished_at = Carbon::parse('2020-01-01 14:10:00');
        $this->createOwnActivity($incoming_started_at, $incoming_finished_at);
        $activity = $this->makeActivity($incoming_started_at, $incoming_finished_at);

        //When
        $overlaps = $this->activity_search->lookupOverLap($activity);

        //Then
        $this->assertCount(1, $overlaps);
    }

    /**
     * @feature Integration
     * @scenario Save User Activity from Time Tracker
     * @case Existing activity found during adding User Activity
     *
     * @test
     */
    public function lookupOverlap_previous_overlap_activity_found()
    {
        //Given
        $found_started_at = Carbon::parse('2020-01-01 11:10:00');
        $found_finished_at = Carbon::parse('2020-01-01 13:10:00');
        $this->createOwnActivity($found_started_at, $found_finished_at);

        $incoming_started_at = Carbon::parse('2020-01-01 13:10:00');
        $incoming_finished_at = Carbon::parse('2020-01-01 14:10:00');
        $activity = $this->makeActivity($incoming_started_at, $incoming_finished_at);

        //When
        $overlaps = $this->activity_search->lookupOverLap($activity);

        //Then
        $this->assertCount(1, $overlaps);
    }

    /**
     * @feature Integration
     * @scenario Save User Activity from Time Tracker
     * @case Existing activity not found during adding User Activity
     *
     * @test
     */
    public function lookupOverlap_previous_overlap_activity_not_found()
    {
        //Given
        $found_started_at = Carbon::parse('2020-01-01 11:10:00');
        $found_finished_at = Carbon::parse('2020-01-01 12:10:00');
        $this->createOwnActivity($found_started_at, $found_finished_at);

        $incoming_started_at = Carbon::parse('2020-01-01 13:10:00');
        $incoming_finished_at = Carbon::parse('2020-01-01 14:10:00');
        $activity = $this->makeActivity($incoming_started_at, $incoming_finished_at);

        //When
        $overlaps = $this->activity_search->lookupOverLap($activity);

        //Then
        $this->assertCount(0, $overlaps);
    }

    private function createOwnActivity(Carbon $started_at, Carbon $finished_at):Activity
    {
        $activity = $this->createActivity($started_at, $finished_at);
        $activity->user()->associate($this->own);
        $activity->save();

        return $activity;
    }

    private function createOtherActivity(Carbon $started_at, Carbon $finished_at):Activity
    {
        $activity = $this->createActivity($started_at, $finished_at);
        $activity->user()->associate($this->other_user);
        $activity->save();

        return $activity;
    }

    private function createActivity(Carbon $started_at, Carbon $finished_at):Activity
    {
        return factory(Activity::class)->create([
            'utc_started_at' => $started_at,
            'utc_finished_at' => $finished_at,
        ]);
    }

    private function makeActivity(Carbon $started_at, Carbon $finished_at):Activity
    {
        return factory(Activity::class)->make([
            'utc_started_at' => $started_at,
            'utc_finished_at' => $finished_at,
            'user_id' => $this->own->id,
        ]);
    }
}
