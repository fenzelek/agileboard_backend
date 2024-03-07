<?php

namespace Tests\Unit\App\Modules\Integration\Services\TimeTracking\NoteMatcher;

use App\Models\Db\Integration\TimeTracking\Note;
use App\Modules\Integration\Services\TimeTracking\NoteMatcher\MultipleNotesMatcher;
use Tests\BrowserKitTestCase;
use Tests\Helpers\ActivityHelper;

class MultipleNotesMatcherTest extends BrowserKitTestCase
{
    use ActivityHelper;

    /** @test */
    public function it_returns_single_activity_with_note_when_before_note_and_no_during_notes()
    {
        $fields = $this->getActivityFields();

        $activity = $this->createActivity($fields);

        $before_note = new Note(['id' => 5123, 'utc_recorded_at' => '2000-01-01 02:01:03']);
        // 375 seconds from start
        $during_notes = collect(
            [new Note(['utc_recorded_at' => '2017-08-01 08:06:15', 'id' => 4312])]
        );

        $this->verifyActivityFields($fields, $activity);

        $multiple_note_matcher = new MultipleNotesMatcher($activity, $before_note, $during_notes);
        $result = $multiple_note_matcher->match();

        // activity is still the same
        $this->verifyActivityFields($fields, $activity, null);

        // verify result
        // verify result
        $this->assertCount(2, $result);
        // 1st activity 89 seconds with assigned before note
        $this->verifyActivityFields(array_merge($fields, [
            'tracked' => 375,
            'overall' => 188, // 375 * 301 / 600

        ]), $result[0], 5123);
        // 2nd activity left time with same assigned during note
        $this->verifyActivityFields(array_merge($fields, [
            'tracked' => $fields['tracked'] - 375,
            'overall' => $fields['overall'] - 188,
            'starts_at' => '2017-08-01 08:06:15',
        ]), $result[1], 4312);
    }

    /** @test */
    public function it_returns_single_activity_with_note_when_no_before_note()
    {
        $fields = $this->getActivityFields();

        $activity = $this->createActivity($fields);

        $during_notes = collect(
            [new Note(['utc_recorded_at' => $fields['starts_at'], 'id' => 4312])]
        );

        $this->verifyActivityFields($fields, $activity);

        $multiple_note_matcher = new MultipleNotesMatcher($activity, null, $during_notes);
        $result = $multiple_note_matcher->match();

        // activity is still the same
        $this->verifyActivityFields($fields, $activity, null);

        // verify result
        $this->assertCount(1, $result);
        $this->verifyActivityFields($fields, $result[0], 4312);
    }

    /** @test */
    public function it_returns_multiple_activities_with_same_note_when_note_added_89_seconds_after()
    {
        $fields = $this->getActivityFields();

        $activity = $this->createActivity($fields);

        // 89 seconds from start
        $during_notes = collect(
            [new Note(['utc_recorded_at' => '2017-08-01 08:01:29', 'id' => 4312])]
        );

        $this->verifyActivityFields($fields, $activity);

        $multiple_note_matcher = new MultipleNotesMatcher($activity, null, $during_notes);
        $result = $multiple_note_matcher->match();

        // activity is still the same
        $this->verifyActivityFields($fields, $activity, null);

        // verify result
        $this->assertCount(2, $result);
        // 1st activity 89 seconds with assigned note
        $this->verifyActivityFields(array_merge($fields, [
            'tracked' => 89,
            'overall' => 45, // 89 * 301 / 600

        ]), $result[0], 4312);
        // 2nd activity left time with same assigned note
        $this->verifyActivityFields(array_merge($fields, [
            'tracked' => $fields['tracked'] - 89,
            'overall' => $fields['overall'] - 45,
            'starts_at' => '2017-08-01 08:01:29',
        ]), $result[1], 4312);
    }

    /** @test */
    public function it_returns_multiple_activities_and_only_one_with_note_when_note_added_90_seconds_after()
    {
        $fields = $this->getActivityFields();

        $activity = $this->createActivity($fields);

        // 90 seconds from start
        $during_notes = collect([
            new Note(['utc_recorded_at' => '2017-08-01 08:01:30', 'id' => 4312]),
        ]);

        $this->verifyActivityFields($fields, $activity);

        $multiple_note_matcher = new MultipleNotesMatcher($activity, null, $during_notes);
        $result = $multiple_note_matcher->match();

        // activity is still the same
        $this->verifyActivityFields($fields, $activity, null);

        // verify result
        $this->assertCount(2, $result);
        // 1st activity 89 seconds with no note
        $this->verifyActivityFields(array_merge($fields, [
            'tracked' => 90,
            'overall' => 45, // 90 * 301 / 600

        ]), $result[0], null);
        // 2nd activity left time with assigned note
        $this->verifyActivityFields(array_merge($fields, [
            'tracked' => $fields['tracked'] - 90,
            'overall' => $fields['overall'] - 45,
            'starts_at' => '2017-08-01 08:01:30',
        ]), $result[1], 4312);
    }

    /** @test */
    public function it_returns_multiple_activities_and_calculates_activity_in_valid_way()
    {
        $fields = $this->getActivityFields();

        $activity = $this->createActivity($fields);

        //  each 97 seconds
        $during_notes = collect([
            new Note(['utc_recorded_at' => '2017-08-01 08:01:37', 'id' => 4312]),
            new Note(['utc_recorded_at' => '2017-08-01 08:03:14', 'id' => 4319]),
        ]);

        $this->verifyActivityFields($fields, $activity);

        $multiple_note_matcher = new MultipleNotesMatcher($activity, null, $during_notes);
        $result = $multiple_note_matcher->match();

        // activity is still the same
        $this->verifyActivityFields($fields, $activity, null);

        // verify result
        $this->assertCount(3, $result);
        // 1st activity 89 seconds with assigned note
        $this->verifyActivityFields(array_merge($fields, [
            'tracked' => 97,
            'overall' => 49, // 97 * 301 / 600

        ]), $result[0], null);
        // 2nd activity left time with assigned note
        $this->verifyActivityFields(array_merge($fields, [
            'tracked' => 97,
            'overall' => 49, // 97 * 301 / 600
            'starts_at' => '2017-08-01 08:01:37',
        ]), $result[1], 4312);
        // 3rd activity left time with assigned note
        $this->verifyActivityFields(array_merge($fields, [
            'tracked' => $fields['tracked'] - 97 - 97,
            'overall' => $fields['overall'] - 49 - 49,
            'starts_at' => '2017-08-01 08:03:14',
        ]), $result[2], 4319);
    }
}
