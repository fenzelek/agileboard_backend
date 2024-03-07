<?php

namespace Tests\Unit\App\Modules\Integration\Services\ActivityTools\ActivityMerger;

use App\Models\Db\Integration\Integration;
use App\Models\Db\Integration\TimeTracking\Activity;
use App\Modules\Integration\Services\ActivityTools\ActivityMerger;
use App\Modules\Integration\Services\ActivityTools\ActivitySearch;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery as m;
use Tests\TestCase;
use function factory;

class ActivityMergerTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @var ActivitySearch|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $activity_search;
    /**
     * @var ActivityMerger
     */
    private $activity_merger;

    public function setUp(): void
    {
        parent::setUp();
        $this->activity_search = m::mock(ActivitySearch::class);

        $this->activity_merger = $this->app->make(ActivityMerger::class, [
            'activity_search' => $this->activity_search,
        ]);
    }

    /**
     * @feature Integration
     * @scenario Save User Activity from Time Tracker
     * @case User Activity not modified because Existing activity not found
     *
     * @test
     */
    public function merge_new_activity_when_existing_not_found()
    {
        //Given
        $incoming_started_at = Carbon::parse('2020-01-01 11:10:00');
        $incoming_finished_at = Carbon::parse('2020-01-01 14:10:00');
        $tracked = 100;
        $active = 10;
        $activity =
            $this->makeActivity($incoming_started_at, $incoming_finished_at, $tracked, $active);

        //When
        $activity_search_expectation = $this->whenActivityWasntFound($activity);
        $merged_activity = $this->activity_merger->merge($activity);

        //Then
        $activity_search_expectation->times(1);
        $this->assertEquals($incoming_started_at, $merged_activity->utc_started_at);
        $this->assertEquals($incoming_finished_at, $merged_activity->utc_finished_at);
        $this->assertSame($tracked, $merged_activity->tracked);
        $this->assertSame($active, $merged_activity->activity);
    }

    /**
     * @feature Integration
     * @scenario Save User Activity from Time Tracker
     * @case User Activity was short because previous overlap activity found
     *
     * @test
     */
    public function merge_short_activity_because_previous_overlap_activity_found()
    {
        //Given
        $found_started_at = Carbon::parse('2020-01-01 10:10:00');
        $found_finished_at = Carbon::parse('2020-01-01 12:10:00');
        $found = $this->makeActivity($found_started_at, $found_finished_at, 99, 99);

        $incoming_started_at = Carbon::parse('2020-01-01 11:10:00');
        $incoming_finished_at = Carbon::parse('2020-01-01 14:10:00');
        $activity = $this->makeActivity($incoming_started_at, $incoming_finished_at, 100, 10);

        //When
        $activity_search_expectation = $this->whenActivityWasFound($activity, $found);
        $merged_activity = $this->activity_merger->merge($activity);

        //Then
        $activity_search_expectation->times(1);
        $this->assertEquals($found_finished_at, $merged_activity->utc_started_at);
        $this->assertEquals($incoming_finished_at, $merged_activity->utc_finished_at);
        $this->assertSame(2 * 60 * 60, $merged_activity->tracked);
        $this->assertSame(2 * 60 * 60 * 10 / 100, $merged_activity->activity);
        $this->assertTrue($merged_activity->exists);
    }

    /**
     * @feature Integration
     * @scenario Save User Activity from Time Tracker
     * @case User Activity was short because previous overlap activity found
     *
     * @test
     */
    public function merge_short_activity_because_after_overlap_activity_found()
    {
        //Given
        $found_started_at = Carbon::parse('2020-01-01 12:10:00');
        $found_finished_at = Carbon::parse('2020-01-01 14:10:00');
        $found = $this->makeActivity($found_started_at, $found_finished_at, 99, 99);

        $incoming_started_at = Carbon::parse('2020-01-01 10:10:00');
        $incoming_finished_at = Carbon::parse('2020-01-01 13:10:00');
        $activity = $this->makeActivity($incoming_started_at, $incoming_finished_at, 100, 10);

        //When
        $activity_search_expectation = $this->whenActivityWasFound($activity, $found);
        $merged_activity = $this->activity_merger->merge($activity);

        //Then
        $activity_search_expectation->times(1);
        $this->assertEquals($incoming_started_at, $merged_activity->utc_started_at);
        $this->assertEquals($found_started_at, $merged_activity->utc_finished_at);
        $this->assertSame(2 * 60 * 60, $merged_activity->tracked);
        $this->assertSame(2 * 60 * 60 * 10 / 100, $merged_activity->activity);
        $this->assertTrue($merged_activity->exists);
    }

    /**
     * @feature Integration
     * @scenario Save User Activity from Time Tracker
     * @case User Activity was short because previous overlap activity found
     *
     * @test
     */
    public function merge_activity_because_consists_overlap_activity_found_completed()
    {
        //Given
        $found_started_at = Carbon::parse('2020-01-01 11:10:00');
        $found_finished_at = Carbon::parse('2020-01-01 13:10:00');
        $found = $this->makeActivity($found_started_at, $found_finished_at, 99, 99);
        $found->save();
        $found_id = $found->id;

        $incoming_started_at = Carbon::parse('2020-01-01 10:10:00');
        $incoming_finished_at = Carbon::parse('2020-01-01 14:10:00');
        $activity = $this->makeActivity($incoming_started_at, $incoming_finished_at, 100, 10);

        //When
        $activity_search_expectation = $this->whenActivityWasFound($activity, $found);
        $merged_activity = $this->activity_merger->merge($activity);

        //Then
        $activity_search_expectation->times(1);
        $this->assertEquals($incoming_started_at, $merged_activity->utc_started_at);
        $this->assertEquals($incoming_finished_at, $merged_activity->utc_finished_at);
        $this->assertSame(4 * 60 * 60, $merged_activity->tracked);
        $this->assertSame(4 * 60 * 60 * 10 / 100, $merged_activity->activity);
        $this->assertTrue($merged_activity->exists);
        $this->assertNull(Activity::find($found_id));
    }

    /**
     * @feature Integration
     * @scenario Save User Activity from Time Tracker
     * @case User Activity saved because two previous overlap activities was shorter
     *
     * @test
     */
    public function merge_activity_because_two_overlap_activities_was_shorter()
    {
        //Given
        $found_started_at = Carbon::parse('2020-01-01 10:10:00');
        $found_finished_at = Carbon::parse('2020-01-01 11:00:00');
        $found_one = $this->makeActivity($found_started_at, $found_finished_at, 99, 99);
        $found_one->save();
        $found_one_id = $found_one->id;

        $found_started_at = Carbon::parse('2020-01-01 11:00:00');
        $found_finished_at = Carbon::parse('2020-01-01 13:50:00');
        $found_two = $this->makeActivity($found_started_at, $found_finished_at, 99, 99);
        $found_two->save();
        $found_two_id = $found_two->id;

        $incoming_started_at = Carbon::parse('2020-01-01 10:00:00');
        $incoming_finished_at = Carbon::parse('2020-01-01 14:00:00');
        $activity = $this->makeActivity($incoming_started_at, $incoming_finished_at, 100, 10);

        //When
        $activity_search_expectation =
            $this->whenActivitiesWasFound($activity, [$found_one, $found_two]);
        $merged_activity = $this->activity_merger->merge($activity);

        //Then
        $activity_search_expectation->times(1);
        $this->assertEquals($incoming_started_at, $merged_activity->utc_started_at);
        $this->assertEquals($incoming_finished_at, $merged_activity->utc_finished_at);
        $this->assertSame(4 * 60 * 60, $merged_activity->tracked);
        $this->assertSame(4 * 60 * 60 * 10 / 100, $merged_activity->activity);
        $this->assertTrue($merged_activity->exists);
        $this->assertNull(Activity::find($found_one_id));
        $this->assertNull(Activity::find($found_two_id));
    }

    /**
     * @feature Integration
     * @scenario Save User Activity from Time Tracker
     * @case User Activity saved because, first overlap start after, second finish after, first
     *     deleted
     *
     * @test
     */
    public function merge_activity_because_one_overlap_was_shorter_second_was_longer()
    {
        //Given
        $found_one_started_at = Carbon::parse('2020-01-01 10:10:00');
        $found_one_finished_at = Carbon::parse('2020-01-01 10:50:00');
        $found_one = $this->makeActivity($found_one_started_at, $found_one_finished_at, 99, 99);
        $found_one->save();
        $found_one_id = $found_one->id;

        $found_two_started_at = Carbon::parse('2020-01-01 11:00:00');
        $found_two_finished_at = Carbon::parse('2020-01-01 14:10:00');
        $found_two = $this->makeActivity($found_two_started_at, $found_two_finished_at, 99, 99);
        $found_two->save();
        $found_two_id = $found_two->id;

        $incoming_started_at = Carbon::parse('2020-01-01 10:00:00');
        $incoming_finished_at = Carbon::parse('2020-01-01 14:00:00');
        $activity = $this->makeActivity($incoming_started_at, $incoming_finished_at, 100, 10);

        //When
        $activity_search_expectation =
            $this->whenActivitiesWasFound($activity, [$found_one, $found_two]);
        $merged_activity = $this->activity_merger->merge($activity);

        //Then
        $activity_search_expectation->times(1);
        $this->assertEquals($incoming_started_at, $merged_activity->utc_started_at);
        $this->assertEquals($found_two_started_at, $merged_activity->utc_finished_at);
        $this->assertSame(1 * 60 * 60, $merged_activity->tracked);
        $this->assertSame(1 * 60 * 60 * 10 / 100, $merged_activity->activity);
        $this->assertTrue($merged_activity->exists);
        $this->assertNull(Activity::find($found_one_id));
        $this->assertTrue($found_two->exists);
    }

    /**
     * @feature Integration
     * @scenario Save User Activity from Time Tracker
     * @case User Activity saved, because first overlap start before, second finish before, second
     *     deleted
     *
     * @test
     */
    public function merge_activity_because_one_overlap_start_before_second_was_shorter()
    {
        //Given
        $found_one_started_at = Carbon::parse('2020-01-01 09:50:00');
        $found_one_finished_at = Carbon::parse('2020-01-01 11:00:00');
        $found_one = $this->makeActivity($found_one_started_at, $found_one_finished_at, 99, 99);
        $found_one->save();
        $found_one_id = $found_one->id;

        $found_two_started_at = Carbon::parse('2020-01-01 11:10:00');
        $found_two_finished_at = Carbon::parse('2020-01-01 12:00:00');
        $found_two = $this->makeActivity($found_two_started_at, $found_two_finished_at, 99, 99);
        $found_two->save();
        $found_two_id = $found_two->id;

        $incoming_started_at = Carbon::parse('2020-01-01 10:00:00');
        $incoming_finished_at = Carbon::parse('2020-01-01 14:00:00');
        $activity = $this->makeActivity($incoming_started_at, $incoming_finished_at, 100, 10);

        //When
        $activity_search_expectation =
            $this->whenActivitiesWasFound($activity, [$found_one, $found_two]);
        $merged_activity = $this->activity_merger->merge($activity);

        //Then
        $activity_search_expectation->times(1);
        $this->assertEquals($found_one_finished_at, $merged_activity->utc_started_at);
        $this->assertEquals($incoming_finished_at, $merged_activity->utc_finished_at);
        $this->assertSame(3 * 60 * 60, $merged_activity->tracked);
        $this->assertSame(3 * 60 * 60 * 10 / 100, $merged_activity->activity);
        $this->assertTrue($merged_activity->exists);
        $this->assertNull(Activity::find($found_two_id));
        $this->assertTrue($found_one->exists);
    }

    /**
     * @feature Integration
     * @scenario Save User Activity from Time Tracker
     * @case User Activity saved, because all activities was shorter than new one, old activities
     *     deleted
     *
     * @test
     */
    public function merge_activity_because_all_activities_shorter_then_new()
    {
        //Given
        $found_one_started_at = Carbon::parse('2020-01-01 10:10:00');
        $found_one_finished_at = Carbon::parse('2020-01-01 11:00:00');
        $found_one = $this->makeActivity($found_one_started_at, $found_one_finished_at, 99, 99);
        $found_one->save();
        $found_one_id = $found_one->id;

        $found_two_started_at = Carbon::parse('2020-01-01 11:00:00');
        $found_two_finished_at = Carbon::parse('2020-01-01 12:00:00');
        $found_two = $this->makeActivity($found_two_started_at, $found_two_finished_at, 99, 99);
        $found_two->save();
        $found_two_id = $found_two->id;

        $found_three_started_at = Carbon::parse('2020-01-01 12:00:00');
        $found_three_finished_at = Carbon::parse('2020-01-01 13:00:00');
        $found_three =
            $this->makeActivity($found_three_started_at, $found_three_finished_at, 99, 99);
        $found_three->save();
        $found_three_id = $found_three->id;

        $incoming_started_at = Carbon::parse('2020-01-01 10:00:00');
        $incoming_finished_at = Carbon::parse('2020-01-01 14:00:00');
        $activity = $this->makeActivity($incoming_started_at, $incoming_finished_at, 100, 10);

        //When
        $activity_search_expectation =
            $this->whenActivitiesWasFound($activity, [$found_one, $found_two, $found_three]);
        $merged_activity = $this->activity_merger->merge($activity);

        //Then
        $activity_search_expectation->times(1);
        $this->assertEquals($incoming_started_at, $merged_activity->utc_started_at);
        $this->assertEquals($incoming_finished_at, $merged_activity->utc_finished_at);
        $this->assertSame(4 * 60 * 60, $merged_activity->tracked);
        $this->assertSame(4 * 60 * 60 * 10 / 100, $merged_activity->activity);
        $this->assertTrue($merged_activity->exists);
        $this->assertNull(Activity::find($found_one_id));
        $this->assertNull(Activity::find($found_two_id));
        $this->assertNull(Activity::find($found_three_id));
    }

    /**
     * @feature Integration
     * @scenario Save User Activity from Time Tracker
     * @case User Activity was short because previous overlap activity found
     *
     * @test
     */
    public function merge_no_save_activity_because_overlap_activity_completed_found()
    {
        //Given
        $found_started_at = Carbon::parse('2020-01-01 10:10:00');
        $found_finished_at = Carbon::parse('2020-01-01 14:10:00');
        $found = $this->makeActivity($found_started_at, $found_finished_at, 99, 99);

        $incoming_started_at = Carbon::parse('2020-01-01 11:10:00');
        $incoming_finished_at = Carbon::parse('2020-01-01 13:10:00');
        $activity = $this->makeActivity($incoming_started_at, $incoming_finished_at, 10, 100);

        //When
        $activity_search_expectation = $this->whenActivityWasFound($activity, $found);
        $merged_activity = $this->activity_merger->merge($activity);

        //Then
        $activity_search_expectation->times(1);
        $this->assertFalse($merged_activity->exists);
    }

    /**
     * @feature Integration
     * @scenario Save User Activity from Time Tracker
     * @case User Activity was short because is duplicated
     *
     * @test
     */
    public function merge_no_save_activity_because_is_duplicated()
    {
        //Given
        $found_started_at = Carbon::parse('2020-01-01 10:10:00');
        $found_finished_at = Carbon::parse('2020-01-01 14:10:00');
        $found = $this->makeActivity($found_started_at, $found_finished_at, 99, 99);

        $activity = $this->makeActivity($found_started_at, $found_finished_at, 10, 100);

        //When
        $activity_search_expectation = $this->whenActivityWasFound($activity, $found);
        $merged_activity = $this->activity_merger->merge($activity);

        //Then
        $activity_search_expectation->times(1);
        $this->assertFalse($merged_activity->exists);
    }

    /**
     * @return m\Expectation|m\ExpectationInterface|m\HigherOrderMessage
     */
    protected function whenActivityWasntFound($activity)
    {
        return $this->activity_search->shouldReceive('lookupOverLap')
            ->with($activity)
            ->andReturn([]);
    }

    /**
     * @return m\Expectation|m\ExpectationInterface|m\HigherOrderMessage
     */
    protected function whenActivityWasFound($activity, $found)
    {
        return $this->activity_search->shouldReceive('lookupOverLap')
            ->with($activity)
            ->andReturn([$found]);
    }

    /**
     * @return m\Expectation|m\ExpectationInterface|m\HigherOrderMessage
     */
    protected function whenActivitiesWasFound($activity, $found)
    {
        return $this->activity_search->shouldReceive('lookupOverLap')
            ->with($activity)
            ->andReturn($found);
    }

    private function makeActivity(Carbon $started_at, Carbon $finished_at, int $tracked, int $activity): Activity
    {
        return factory(Activity::class)->make([
            'utc_started_at' => $started_at,
            'utc_finished_at' => $finished_at,
            'tracked' => $tracked,
            'activity' => $activity,
        ]);
    }
}
