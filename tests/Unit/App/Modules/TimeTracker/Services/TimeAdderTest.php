<?php

namespace Tests\Unit\App\Modules\TimeTracker\Services;

use App\Models\Db\Integration\TimeTracking\Activity;
use App\Modules\TimeTracker\Services\TimeAdder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TimeAdderTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @feature Time Tracker
     * @scenario Sum time in activates entries
     * @case Get summary time from activities beginning and ending in one day
     *
     * @test
     * @covers \App\Modules\TimeTracker\Services\TimeAdder::sumTimeInActivities
     */
    public function sumTimeInActivities_get_summary_time_from_activities_beginning_and_ending_in_one_day()
    {

        // Given
        $activities[] = new Activity([
            'utc_started_at' => Carbon::now()->setHour(8)->setMinute(0)->setSecond(0)->toDateTimeString(),
            'utc_finished_at' => Carbon::now()->setHour(9)->setMinute(30)->setSecond(0)->toDateTimeString(),
        ]);

        $activities[] = new Activity([
            'utc_started_at' => Carbon::now()->setHour(10)->setMinute(0)->setSecond(0)->toDateTimeString(),
            'utc_finished_at' => Carbon::now()->setHour(10)->setMinute(30)->setSecond(0)->toDateTimeString(),
        ]);

        // When
        /** @var TimeAdder $service */
        $service = $this->app->make(TimeAdder::class);
        $time_summary = $service->sumTimeInActivities(collect($activities), Carbon::now());

        // Then
        $this->assertCount(2, $time_summary);
        $this->assertSame($activities[0], $time_summary[0]); // 2 hours
        $this->assertSame($activities[1], $time_summary[1]); // 2 hours
    }

    /**
     * @feature Time Tracker
     * @scenario Sum time in activates entries
     * @case Get summary time from activities beginning in prev day and ending in current day
     *
     * @test
     * @covers \App\Modules\TimeTracker\Services\TimeAdder::sumTimeInActivities
     */
    public function sumTimeInActivities_get_summary_time_from_activities_beginning_in_prev_day_and_ending_in_current_day()
    {

        // Given
        $activities[] = new Activity([
            'utc_started_at' => Carbon::now()->subDay()->setHour(15)->setMinute(0)->setSecond(0)->toDateTimeString(),
            'utc_finished_at' => Carbon::now()->setHour(9)->setMinute(0)->setSecond(0)->toDateTimeString(),
            'tracked' => 10000000,
        ]);

        $activities[] = new Activity([
            'utc_started_at' => Carbon::now()->setHour(10)->setMinute(0)->setSecond(0)->toDateTimeString(),
            'utc_finished_at' => Carbon::now()->setHour(10)->setMinute(30)->setSecond(0)->toDateTimeString(),
            'tracked' => 30,
        ]);

        // When
        /** @var TimeAdder $service */
        $service = $this->app->make(TimeAdder::class);
        $time_summary = $service->sumTimeInActivities(collect($activities), Carbon::now());

        // Then
        $this->assertCount(2, $time_summary);
        $this->assertSame(9 * 60 * 60, $time_summary[0]['tracked']); // 9 hours
        $this->assertSame(30, $time_summary[1]['tracked']); // 0.5 hours
    }

    /**
     * @feature Time Tracker
     * @scenario Sum time in activates entries
     * @case Get summary time from activities beginning in prev day and ending in next day
     *
     * @test
     * @covers \App\Modules\TimeTracker\Services\TimeAdder::sumTimeInActivities
     */
    public function sumTimeInActivities_get_summary_time_from_activities_beginning_in_prev_day_and_ending_in_next_day()
    {

        // Given
        $activities[] = new Activity([
            'utc_started_at' => Carbon::now()->subDay()->setHour(15)->setMinute(0)->setSecond(0)->toDateTimeString(),
            'utc_finished_at' => Carbon::now()->setHour(4)->setMinute(0)->setSecond(0)->toDateTimeString(),
        ]);

        $activities[] = new Activity([
            'utc_started_at' => Carbon::now()->setHour(10)->setMinute(0)->setSecond(0)->toDateTimeString(),
            'utc_finished_at' => Carbon::now()->setHour(10)->setMinute(30)->setSecond(0)->toDateTimeString(),
            'tracked' => 30,
        ]);

        $activities[] = new Activity([
            'utc_started_at' => Carbon::now()->setHour(23)->setMinute(0)->setSecond(0)->toDateTimeString(),
            'utc_finished_at' => Carbon::now()->addDay()->setHour(2)->setMinute(0)->setSecond(0)->toDateTimeString(),
        ]);

        // When
        /** @var TimeAdder $service */
        $service = $this->app->make(TimeAdder::class);
        $time_summary = $service->sumTimeInActivities(collect($activities), Carbon::now());

        // Then
        $this->assertCount(3, $time_summary);
        $this->assertSame(4 * 60 * 60, $time_summary[0]['tracked']); // 9 hours
        $this->assertSame(30, $time_summary[1]['tracked']); // 0.5 hours
        $this->assertSame(60 * 60 - 1, $time_summary[2]['tracked']); // 0.5 hours
    }
}
