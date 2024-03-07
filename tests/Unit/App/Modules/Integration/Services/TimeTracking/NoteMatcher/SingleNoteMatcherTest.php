<?php

namespace Tests\Unit\App\Modules\Integration\Services\TimeTracking\NoteMatcher;

use App\Models\Db\Integration\TimeTracking\Note;
use App\Modules\Integration\Services\TimeTracking\NoteMatcher\SingleNoteMatcher;
use Tests\BrowserKitTestCase;
use Tests\Helpers\ActivityHelper;

class SingleNoteMatcherTest extends BrowserKitTestCase
{
    use ActivityHelper;

    /** @test */
    public function it_returns_not_modified_activity_when_no_before_note()
    {
        $fields = $this->getActivityFields();
        $activity = $this->createActivity($fields);

        $this->verifyActivityFields($fields, $activity);

        $single_note_matcher = new SingleNoteMatcher($activity, null);
        $result = $single_note_matcher->match();

        // activity is still the same
        $this->verifyActivityFields($fields, $activity);

        // verify result
        $this->assertCount(1, $result);
        $this->verifyActivityFields($fields, $result[0]);
    }

    /** @test */
    public function it_returns_modified_activity_when_there_is_before_note()
    {
        $fields = $this->getActivityFields();
        $activity = $this->createActivity($fields);

        $this->verifyActivityFields($fields, $activity);

        $before_note = new Note(['id' => 562]);

        $single_note_matcher = new SingleNoteMatcher($activity, $before_note);
        $result = $single_note_matcher->match();

        // activity is still the same
        $this->verifyActivityFields($fields, $activity);

        // verify result
        $this->assertCount(1, $result);
        $this->verifyActivityFields($fields, $result[0], 562);
    }
}
