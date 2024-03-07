<?php

namespace Tests\Unit\App\Modules\Integration\Services\TimeTracking\NoteMatcher;

use App\Models\Db\Integration\TimeTracking\Note;
use App\Models\Other\Integration\TimeTracking\Activity;
use App\Modules\Integration\Services\TimeTracking\NoteMatcher\BeforeNoteVerifier;
use Tests\BrowserKitTestCase;
use Mockery as m;
use Tests\Helpers\ActivityHelper;

class BeforeNoteVerifierTest extends BrowserKitTestCase
{
    use ActivityHelper;

    /** @test */
    public function it_gets_null_when_before_note_is_empty()
    {
        $before_note = null;

        $activity = m::mock(Activity::class);

        $verifier = m::spy(BeforeNoteVerifier::class)->makePartial();
        $this->assertNull($verifier->get($activity, $before_note));

        $verifier->shouldNotHaveReceived('shouldBeforeNoteBeUsed');
    }

    /** @test */
    public function it_returns_same_before_note_when_same_day()
    {
        $note_fields = ['id' => 562, 'utc_recorded_at' => '2017-08-01 01:00:00'];
        $before_note = new Note($note_fields);

        $fields = $this->getActivityFields();
        $fields['starts_at'] = '2017-08-01 08:00:00';

        $activity = $this->createActivity($fields);

        $verifier = m::spy(BeforeNoteVerifier::class)->makePartial();
        $result = $verifier->get($activity, $before_note);
        $this->assertTrue($result instanceof Note);
        $this->assertSame($note_fields, $result->toArray());

        $verifier->shouldHaveReceived('shouldBeforeNoteBeUsed')->once();
    }

    /** @test */
    public function it_returns_same_before_note_when_before_day_and_diff_is_less_than_4_hours()
    {
        $note_fields = ['id' => 562, 'utc_recorded_at' => '2017-07-31 21:00:00'];
        $before_note = new Note($note_fields);

        $fields = $this->getActivityFields();
        $fields['starts_at'] = '2017-08-01 01:00:00';

        $activity = $this->createActivity($fields);

        $verifier = m::spy(BeforeNoteVerifier::class)->makePartial();
        $result = $verifier->get($activity, $before_note);
        $this->assertTrue($result instanceof  Note);
        $this->assertSame($note_fields, $result->toArray());

        $verifier->shouldHaveReceived('shouldBeforeNoteBeUsed')->once();
    }

    /** @test */
    public function it_returns_null_when_before_day_and_diff_is_more_than_4_hours()
    {
        $note_fields = ['id' => 562, 'utc_recorded_at' => '2017-07-31 20:59:59'];
        $before_note = new Note($note_fields);

        $fields = $this->getActivityFields();
        $fields['starts_at'] = '2017-08-01 01:00:00';

        $activity = $this->createActivity($fields);

        $verifier = m::spy(BeforeNoteVerifier::class)->makePartial();
        $this->assertNull($verifier->get($activity, $before_note));

        $verifier->shouldHaveReceived('shouldBeforeNoteBeUsed')->once();
    }
}
