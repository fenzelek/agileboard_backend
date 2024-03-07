<?php

namespace Tests\Unit\App\Modules\Agile\Events;

use App\Helpers\BroadcastChannels;
use App\Helpers\EventTypes;
use App\Models\Db\Project;
use App\Models\Db\Ticket;
use App\Modules\Agile\Events\SetFlagToHideTicketEvent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SetFlagToHideTicketEventTest extends TestCase
{
    use DatabaseTransactions;

    private $project;
    private $ticket;
    private $event;

    public function setUp():void
    {
        parent::setUp();

        $this->project = factory(Project::class)->create();
        $this->ticket = factory(Ticket::class)->make(['id' => 1, 'project_id' => $this->project->id]);
        $this->event = new SetFlagToHideTicketEvent($this->project, $this->ticket, 0);
    }

    /** @test */
    public function getProject()
    {
        $project = $this->event->getProject();

        $this->assertSame($this->project->id, $project->id);
    }

    /** @test */
    public function getMessage()
    {
        $this->assertSame([], $this->event->getMessage());
    }

    /** @test */
    public function getRecipients()
    {
        $this->assertSame([], $this->event->getRecipients());
    }

    /** @test */
    public function getType()
    {
        $this->assertSame(EventTypes::TICKET_SET_HIDE_FLAG, $this->event->getType());
    }

    /** @test */
    public function getAttachments()
    {
        $this->assertSame([], $this->event->getAttachments());
    }

    /** @test */
    public function getBroadcastChannel()
    {
        $this->assertSame(BroadcastChannels::TICKET_CHANGE_MIN, $this->event->getBroadcastChannel());
    }

    /** @test */
    public function getBroadcastData()
    {
        $data = $this->event->getBroadcastData();

        $this->assertSame($this->project->id, $data['project_id']);
        $this->assertSame($this->ticket->id, $data['ticket_id']);
        $this->assertSame(0, $data['sprint_id']);
    }
}
