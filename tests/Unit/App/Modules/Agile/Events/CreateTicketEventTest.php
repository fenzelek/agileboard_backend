<?php

namespace Tests\Unit\App\Modules\Agile\Events;

use App\Helpers\BroadcastChannels;
use App\Helpers\EventTypes;
use App\Models\Db\Project;
use App\Models\Db\Role;
use App\Models\Db\Ticket;
use App\Models\Db\User;
use App\Models\Other\RoleType;
use App\Modules\Agile\Events\CreateTicketEvent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CreateTicketEventTest extends TestCase
{
    use DatabaseTransactions;

    private $project;
    private $ticket;
    private $event;
    private $admin;
    private $owner;
    private $current_user;

    public function setUp():void
    {
        parent::setUp();

        $this->project = factory(Project::class)->create();
        $this->current_user = factory(User::class)->create();
        $this->owner = factory(User::class)->create();
        $this->admin = factory(User::class)->create();
        $this->project->users()->attach($this->owner, ['role_id' => Role::findByName(RoleType::OWNER)->id]);
        $this->project->users()->attach($this->admin, ['role_id' => Role::findByName(RoleType::ADMIN)->id]);
        $this->project->users()->attach($this->current_user, ['role_id' => Role::findByName(RoleType::ADMIN)->id]);
        $this->project->users()->attach(factory(User::class)->create(), ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);
        $this->ticket = factory(Ticket::class)->make(['id' => 1, 'project_id' => $this->project->id]);
        $this->event = new CreateTicketEvent($this->project, $this->ticket, $this->current_user);
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
        $this->assertSame('Zadanie [' . $this->ticket->title . '] "' . $this->ticket->name . '" zostało utworzone przez ' . $this->current_user->first_name . ' ' . $this->current_user->last_name . '.', $data['content']);
    }

    /** @test */
    public function getRecipients()
    {
        $users = $this->event->getRecipients();

        $this->assertSame(3, count($users));
        $this->assertSame($this->owner->id, $users[0]->id);
        $this->assertSame($this->admin->id, $users[1]->id);
        $this->assertSame($this->current_user->id, $users[2]->id);
    }

    /** @test */
    public function getType()
    {
        $this->assertSame(EventTypes::TICKET_STORE, $this->event->getType());
    }

    /** @test */
    public function getAttachments()
    {
        $this->assertSame([], $this->event->getAttachments());
    }

    /** @test */
    public function getBroadcastChannel()
    {
        $this->assertSame(BroadcastChannels::TICKET_STORE, $this->event->getBroadcastChannel());
    }

    /** @test */
    public function getBroadcastData()
    {
        $data = $this->event->getBroadcastData();

        $this->assertSame($this->project->id, $data['project_id']);
        $this->assertSame($this->ticket->id, $data['ticket_id']);
        $this->assertSame($this->ticket->sprint_id, $data['sprint_id']);
    }
}
