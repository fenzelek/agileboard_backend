<?php

namespace Tests\Unit\App\Modules\Agile\Events;

use App\Helpers\BroadcastChannels;
use App\Helpers\EventTypes;
use App\Models\Db\Project;
use App\Models\Db\Role;
use App\Models\Db\Ticket;
use App\Models\Db\TicketComment;
use App\Models\Db\User;
use App\Models\Other\RoleType;
use App\Modules\Agile\Events\UpdateCommentEvent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UpdateCommentEventTest extends TestCase
{
    use DatabaseTransactions;

    private $project;
    private $ticket;
    private $event;
    private $admin;
    private $owner;
    private $author;
    private $assigned;
    private $reporter;
    private $comment;

    public function setUp():void
    {
        parent::setUp();

        $this->project = factory(Project::class)->create();
        $this->owner = factory(User::class)->create();
        $this->admin = factory(User::class)->create();
        $this->author = factory(User::class)->create();
        $this->assigned = factory(User::class)->create();
        $this->reporter = factory(User::class)->create();
        $this->project->users()->attach($this->owner, ['role_id' => Role::findByName(RoleType::OWNER)->id]);
        $this->project->users()->attach($this->admin, ['role_id' => Role::findByName(RoleType::ADMIN)->id]);
        $this->project->users()->attach($this->author, ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);
        $this->project->users()->attach($this->assigned, ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);
        $this->project->users()->attach($this->reporter, ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);
        $this->ticket = factory(Ticket::class)->make(['id' => 1, 'project_id' => $this->project->id, 'assigned_id' => $this->assigned->id, 'reporter_id' => $this->reporter->id]);
        $this->comment = factory(TicketComment::class)->make(['id' => 1, 'ticket_id' => $this->ticket->id, 'user_id' => $this->author->id]);
        $this->event = new UpdateCommentEvent($this->project, $this->ticket, $this->comment);
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
        $users = $this->event->getRecipients();

        $this->assertSame(2, count($users));
        $this->assertSame($this->assigned->id, $users[0]->id);
        $this->assertSame($this->reporter->id, $users[1]->id);
    }

    /** @test */
    public function getType()
    {
        $this->assertSame(EventTypes::TICKET_COMMENT_UPDATE, $this->event->getType());
    }

    /** @test */
    public function getAttachments()
    {
        $this->assertSame([], $this->event->getAttachments());
    }

    /** @test */
    public function getBroadcastChannel()
    {
        $this->assertSame(BroadcastChannels::TICKET_COMMENT, $this->event->getBroadcastChannel());
    }

    /** @test */
    public function getBroadcastData()
    {
        $data = $this->event->getBroadcastData();

        $this->assertSame($this->project->id, $data['project_id']);
    }
}
