<?php

namespace Tests\Unit\App\Modules\Integration\Services\TimeTracking\Processor;

use App\Models\Db\Integration\Integration;
use App\Models\Other\Integration\TimeTracking\Note;
use App\Models\Db\Integration\TimeTracking\Note as NoteModel;
use App\Modules\Integration\Services\TimeTracking\Processor\NotesProcessor;
use Carbon\Carbon;
use stdClass;
use Tests\BrowserKitTestCase;
use Mockery as m;

class NotesProcessorTest extends BrowserKitTestCase
{
    /** @test */
    public function it_updates_record_when_they_exist_and_creates_when_they_dont_exist()
    {
        $note = m::mock(NoteModel::class);

        $integration = new Integration(['id' => 523]);

        $date = Carbon::now()->subDays(6);

        $notes = collect([
            new Note(150, 132, 323, 'abc', Carbon::now()),
            new Note(155, 175, 1512, 'new note', $date),
            new Note(199, 132, 323, 'def', Carbon::now()),
        ]);

        $builder_1 = m::mock(stdClass::class);
        $builder_2 = m::mock(stdClass::class);
        $builder_3 = m::mock(stdClass::class);

        $existing_model_1 = m::mock(stdClass::class);
        $existing_model_3 = m::mock(stdClass::class);

        // 1st record should be updated
        $note->shouldReceive('where')->once()->with('integration_id', 523)->andReturn($builder_1);
        $builder_1->shouldReceive('where')->once()->with('external_note_id', 150)
            ->andReturn($builder_1);
        $builder_1->shouldReceive('first')->once()->withNoArgs()->andReturn($existing_model_1);
        $existing_model_1->shouldReceive('update')->once()->with(['content' => 'abc']);

        // 2nd record should be created
        $note->shouldReceive('where')->once()->with('integration_id', 523)->andReturn($builder_2);
        $builder_2->shouldReceive('where')->once()->with('external_note_id', 155)
            ->andReturn($builder_2);
        $builder_2->shouldReceive('first')->once()->withNoArgs()->andReturn(null);

        $note->shouldReceive('create')->once()->with([
            'integration_id' => 523,
            'external_note_id' => 155,
            'external_project_id' => 175,
            'external_user_id' => 1512,
            'content' => 'new note',
            'utc_recorded_at' => $date,
        ]);

        // 3rd record should be created
        $note->shouldReceive('where')->once()->with('integration_id', 523)->andReturn($builder_3);
        $builder_3->shouldReceive('where')->once()->with('external_note_id', 199)
            ->andReturn($builder_3);
        $builder_3->shouldReceive('first')->once()->withNoArgs()->andReturn($existing_model_3);
        $existing_model_3->shouldReceive('update')->once()->with(['content' => 'def']);

        $processor = new NotesProcessor($note);

        $processor->save($integration, $notes);
        $this->assertTrue(true);
    }
}
