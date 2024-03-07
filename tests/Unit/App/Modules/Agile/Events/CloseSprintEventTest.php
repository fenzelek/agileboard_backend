<?php

namespace Tests\Unit\App\Modules\Agile\Events;

use App\Helpers\BroadcastChannels;
use App\Helpers\EventTypes;
use App\Models\Db\Project;
use App\Models\Db\Sprint;
use App\Modules\Agile\Events\CloseSprintEvent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CloseSprintEventTest extends TestCase
{
    use DatabaseTransactions;

    private $project;
    private $sprint;
    private $event;
    private $destination_sprint;

    public function setUp():void
    {
        parent::setUp();

        $this->project = factory(Project::class)->create();
        $this->sprint = factory(Sprint::class)->create(['project_id' => $this->project->id]);
        $this->destination_sprint = factory(Sprint::class)->create(['project_id' => $this->project->id]);
        $this->event = new CloseSprintEvent($this->project, $this->sprint, $this->destination_sprint->id);
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
        $this->assertSame(EventTypes::SPRINT_CLOSE, $this->event->getType());
    }

    /** @test */
    public function getAttachments()
    {
        $this->assertSame([], $this->event->getAttachments());
    }

    /** @test */
    public function getBroadcastChannel()
    {
        $this->assertSame(BroadcastChannels::SPRINT_CHANGE_STATUS, $this->event->getBroadcastChannel());
    }

    /** @test */
    public function getBroadcastData()
    {
        $data = $this->event->getBroadcastData();

        $this->assertSame($this->project->id, $data['project_id']);
        $this->assertSame($this->sprint->id, $data['sprint_id']);
        $this->assertSame($this->destination_sprint->id, $data['destination_sprint_id']);
        $this->assertSame(Sprint::CLOSED, $data['status']);
    }
}
