<?php

namespace Tests\Unit\App\Modules\Integration\Services\TimeTracking\NoteMatcher;

use App\Models\Db\Integration\TimeTracking\Note;
use App\Models\Other\Integration\TimeTracking\Activity;
use App\Modules\Integration\Services\TimeTracking\NoteMatcher\BeforeNoteVerifier;
use App\Modules\Integration\Services\TimeTracking\NoteMatcher\MultipleNotesMatcher;
use App\Modules\Integration\Services\TimeTracking\NoteMatcher\NoteMatcher;
use App\Modules\Integration\Services\TimeTracking\NoteMatcher\SingleNoteMatcher;
use Tests\BrowserKitTestCase;
use Mockery as m;

class NoteMatcherTest extends BrowserKitTestCase
{
    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_runs_single_note_matcher_when_no_during_notes()
    {
        // @todo this test doesn't test whether valid arguments were passed to object - same for
        // all other tests method in this file

        $during_notes = collect();
        $before_note = null;

        $activity = m::mock(Activity::class);
        $activity->shouldReceive('dummyMethod')->andReturn('foo');

        $before_note_verifier = m::spy(BeforeNoteVerifier::class);

        $single_note_matcher = m::mock('overload:' . SingleNoteMatcher::class)->makePartial();
        $single_note_matcher->shouldReceive('match')->withNoArgs()->once()->andReturn('test');

        $matcher = new NoteMatcher($before_note_verifier);
        $result = $matcher->find($activity, null, $during_notes);

        $this->assertSame('test', $result);

        $before_note_verifier->shouldHaveReceived('get')->once()->with(m::on(function ($arg) {
            return $arg instanceof Activity && $arg->dummyMethod() == 'foo';
        }), null);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_runs_single_note_matcher_when_no_during_notes_and_before_note()
    {
        $during_notes = collect();
        $before_note = new Note(['id' => 5123123]);

        $activity = m::mock(Activity::class);
        $activity->shouldReceive('dummyMethod')->andReturn('foo');

        $before_note_verifier = m::spy(BeforeNoteVerifier::class);

        $single_note_matcher = m::mock('overload:' . SingleNoteMatcher::class)->makePartial();
        $single_note_matcher->shouldReceive('match')->withNoArgs()->once()->andReturn('test');

        $matcher = new NoteMatcher($before_note_verifier);
        $result = $matcher->find($activity, $before_note, $during_notes);

        $this->assertSame('test', $result);

        $before_note_verifier->shouldHaveReceived('get')->once()->with(m::on(function ($arg) {
            return $arg instanceof Activity && $arg->dummyMethod() == 'foo';
        }), m::on(function ($arg) {
            return $arg instanceof Note && $arg->id == 5123123;
        }));
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_runs_multiple_note_matcher_when_there_are_during_notes()
    {
        $during_notes = collect([1]);
        $before_note = null;

        $activity = m::mock(Activity::class);
        $activity->shouldReceive('dummyMethod')->andReturn('foo');

        $before_note_verifier = m::spy(BeforeNoteVerifier::class);

        $single_note_matcher = m::mock('overload:' . MultipleNotesMatcher::class)->makePartial();
        $single_note_matcher->shouldReceive('match')->withNoArgs()->once()->andReturn('test');

        $matcher = new NoteMatcher($before_note_verifier);
        $result = $matcher->find($activity, null, $during_notes);

        $this->assertSame('test', $result);

        $before_note_verifier->shouldHaveReceived('get')->once()->with(m::on(function ($arg) {
            return $arg instanceof Activity && $arg->dummyMethod() == 'foo';
        }), null);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_runs_multiple_note_matcher_when_there_are_during_notes_and_before_note()
    {
        $during_notes = collect([1]);
        $before_note = new Note(['id' => 5123123]);

        $activity = m::mock(Activity::class);
        $activity->shouldReceive('dummyMethod')->andReturn('foo');

        $before_note_verifier = m::spy(BeforeNoteVerifier::class);

        $single_note_matcher = m::mock('overload:' . MultipleNotesMatcher::class)->makePartial();
        $single_note_matcher->shouldReceive('match')->withNoArgs()->once()->andReturn('test');

        $matcher = new NoteMatcher($before_note_verifier);
        $result = $matcher->find($activity, $before_note, $during_notes);

        $this->assertSame('test', $result);

        $before_note_verifier->shouldHaveReceived('get')->once()->with(m::on(function ($arg) {
            return $arg instanceof Activity && $arg->dummyMethod() == 'foo';
        }), m::on(function ($arg) {
            return $arg instanceof Note && $arg->id == 5123123;
        }));
    }
}
