<?php

namespace Tests\Unit\App\Modules\Agile\Events;

use App\Helpers\BroadcastChannels;
use App\Helpers\EventTypes;
use App\Models\Db\Project;
use App\Modules\Agile\Events\ChangePrioritySprintEvent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ChangePrioritySprintEventTest extends TestCase
{
    use DatabaseTransactions;

    private $project;
    private $event;

    public function setUp():void
    {
        parent::setUp();

        $this->project = factory(Project::class)->create();
        $this->event = new ChangePrioritySprintEvent($this->project);
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
        $this->assertSame(EventTypes::SPRINT_CHANGE_PRIORITY, $this->event->getType());
    }

    /** @test */
    public function getAttachments()
    {
        $this->assertSame([], $this->event->getAttachments());
    }

    /** @test */
    public function getBroadcastChannel()
    {
        $this->assertSame(BroadcastChannels::SPRINT_CHANGE_PRIORITY, $this->event->getBroadcastChannel());
    }

    /** @test */
    public function getBroadcastData()
    {
        $data = $this->event->getBroadcastData();

        $this->assertSame($this->project->id, $data['project_id']);
    }
}
