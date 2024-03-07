<?php

namespace Tests\Unit\App\Modules\Integration\Services\TimeTracking\Processor\ActivitiesProcessor;

use App\Models\Db\Integration\TimeTracking\Activity as ActivityModel;
use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\Integration\TimeTracking\Note;
use App\Models\Db\Project;
use App\Models\Db\Ticket;
use App\Modules\Integration\Services\TimeTracking\NoteMatcher\NoteMatcher;
use App\Modules\Integration\Services\TimeTracking\Processor\ActivitiesProcessor;
use Tests\BrowserKitTestCase;
use Mockery as m;
use Tests\Helpers\ActivityHelper;

class FindTicketTest extends BrowserKitTestCase
{
    use ActivityHelper;

    /** @test */
    public function it_returns_null_when_cannot_match_text()
    {
        $note_matcher = m::mock(NoteMatcher::class);
        $activity = m::mock(ActivityModel::class);
        $ticket = m::mock(Ticket::class);
        $note = m::mock(Note::class);

        $real_activity = new Activity();
        $real_activity->setRelation('timeTrackingNote', new Note(['content' => 'sample']));
        $real_activity->setRelation('project', new Project(['id' => 15, 'short_name' => 'XAC']));

        $processor = new ActivitiesProcessor($note_matcher, $activity, $ticket, $note);

        $this->assertNull($processor->findTicket($real_activity));
    }

    /** @test */
    public function it_returns_null_when_no_time_tracking_note_assigned()
    {
        $note_matcher = m::mock(NoteMatcher::class);
        $activity = m::mock(ActivityModel::class);
        $ticket = m::mock(Ticket::class);
        $note = m::mock(Note::class);

        $real_activity = new Activity();
        $real_activity->setRelation('project', new Project(['id' => 15, 'short_name' => 'XAC']));

        $processor = new ActivitiesProcessor($note_matcher, $activity, $ticket, $note);

        $this->assertNull($processor->findTicket($real_activity));
    }

    /** @test */
    public function it_returns_null_when_no_project_assigned()
    {
        $note_matcher = m::mock(NoteMatcher::class);
        $activity = m::mock(ActivityModel::class);
        $ticket = m::mock(Ticket::class);
        $note = m::mock(Note::class);

        $real_activity = new Activity();
        $real_activity->setRelation('timeTrackingNote', new Note(['content' => 'sample']));

        $processor = new ActivitiesProcessor($note_matcher, $activity, $ticket, $note);

        $this->assertNull($processor->findTicket($real_activity));
    }

    /** @test */
    public function it_returns_null_when_matched_string_but_cannot_find_ticket()
    {
        $note_matcher = m::mock(NoteMatcher::class);
        $activity = m::mock(ActivityModel::class);
        $ticket = m::mock(Ticket::class);
        $note = m::mock(Note::class);

        $real_activity = new Activity();
        $real_activity->setRelation('timeTrackingNote', new Note(['content' => 'abc33 XAC-152 31rer']));
        $real_activity->setRelation('project', new Project(['id' => 15, 'short_name' => 'XAC']));

        $ticket->shouldReceive('where')->once()->with('project_id', 15)->andReturn($ticket);
        $ticket->shouldReceive('where')->once()->with('title', 'XAC-152')->andReturn($ticket);
        $ticket->shouldReceive('first')->once()->andReturn(null);

        $processor = new ActivitiesProcessor($note_matcher, $activity, $ticket, $note);

        $this->assertNull($processor->findTicket($real_activity));
    }

    /** @test */
    public function it_returns_ticket_when_matched_string_and_can_find_ticket()
    {
        $note_matcher = m::mock(NoteMatcher::class);
        $activity = m::mock(ActivityModel::class);
        $ticket = m::mock(Ticket::class);
        $note = m::mock(Note::class);

        $real_activity = new Activity();
        $real_activity->setRelation('timeTrackingNote', new Note(['content' => 'abc33 XAC-152 31rer']));
        $real_activity->setRelation('project', new Project(['id' => 15, 'short_name' => 'XAC']));

        $ticket->shouldReceive('where')->once()->with('project_id', 15)->andReturn($ticket);
        $ticket->shouldReceive('where')->once()->with('title', 'XAC-152')->andReturn($ticket);
        $ticket->shouldReceive('first')->once()->andReturn('ticket_object');

        $processor = new ActivitiesProcessor($note_matcher, $activity, $ticket, $note);

        $this->assertSame('ticket_object', $processor->findTicket($real_activity));
    }
}
