<?php

namespace Tests\Unit\App\Modules\Agile\Events;

use App\Helpers\EventTypes;
use App\Models\Db\Project;
use App\Models\Db\Role;
use App\Models\Db\Ticket;
use App\Models\Db\User;
use App\Models\Other\RoleType;
use App\Modules\Agile\Events\ExpiredScheduledDateEvent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ExpiredScheduledDateEventTest extends TestCase
{
    use DatabaseTransactions;

    private $project;
    private $event;
    private $ticket;
    private $user_assigned;

    public function setUp():void
    {
        parent::setUp();

        $this->project = factory(Project::class)->create();
        $this->user_assigned = factory(User::class)->create();
        $this->project->users()->attach(factory(User::class)->create(), ['role_id' => Role::findByName(RoleType::OWNER)->id]);
        $this->project->users()->attach($this->user_assigned, ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);
        $this->ticket = factory(Ticket::class)->make(['id' => 1, 'project_id' => $this->project->id, 'assigned_id' => $this->user_assigned->id]);

        $this->event = new ExpiredScheduledDateEvent($this->project, $this->ticket);
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
        $data = $this->event->getMessage();

        $this->assertSame('[' . $this->ticket->title . '] ' . $this->ticket->name, $data['title']);
        $this->assertSame($this->ticket->title, $data['url_title']);
        $this->assertSame(config('app_settings.welcome_absolute_url') . '/projects/' . $this->project->id . '/ticket/' . $this->ticket->title, $data['url']);
        $this->assertSame(
            'W zadaniu [' . $this->ticket->title . '] "' . $this->ticket->name . '" został przekroczony zaplanowany dzień zakończenia prac.',
            $data['content']
        );
    }

    /** @test */
    public function getRecipients()
    {
        $users = $this->event->getRecipients();

        $this->assertSame(1, count($users));
        $this->assertSame($this->user_assigned->id, $users[0]->id);
    }

    /** @test */
    public function getType()
    {
        $this->assertSame(EventTypes::TICKET_EXPIRED_SCHEDULED_DATE, $this->event->getType());
    }

    /** @test */
    public function getAttachments()
    {
        $this->assertSame([], $this->event->getAttachments());
    }

    /** @test */
    public function getBroadcastChannel()
    {
        $this->assertSame('', $this->event->getBroadcastChannel());
    }

    /** @test */
    public function getBroadcastData()
    {
        $this->assertSame([], $this->event->getBroadcastData());
    }
}
