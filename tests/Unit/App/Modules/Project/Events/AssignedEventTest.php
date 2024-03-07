<?php

namespace Tests\Unit\App\Modules\Project\Events;

use App\Helpers\EventTypes;
use App\Models\Db\Project;
use App\Models\Db\Role;
use App\Models\Db\User;
use App\Models\Other\RoleType;
use App\Modules\Project\Events\AssignedEvent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AssignedEventTest extends TestCase
{
    use DatabaseTransactions;

    private $project;
    private $event;
    private $admin;
    private $owner;
    private $assigned;

    public function setUp():void
    {
        parent::setUp();

        $this->project = factory(Project::class)->create();
        $this->owner = factory(User::class)->create();
        $this->admin = factory(User::class)->create();
        $this->assigned = factory(User::class)->create();
        $this->project->users()->attach($this->owner, ['role_id' => Role::findByName(RoleType::OWNER)->id]);
        $this->project->users()->attach($this->admin, ['role_id' => Role::findByName(RoleType::ADMIN)->id]);
        $this->project->users()->attach(factory(User::class)->create(), ['role_id' => Role::findByName(RoleType::DEVELOPER)->id]);
        $this->event = new AssignedEvent($this->project, $this->assigned);
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

        $this->assertSame($this->project->name, $data['title']);
        $this->assertSame($this->project->name, $data['url_title']);
        $this->assertSame(config('app_settings.welcome_absolute_url') . '/projects/' . $this->project->id . '/agile', $data['url']);
        $this->assertSame('Do projektu ' . $this->project->name . ' został przypisany użytkownik ' . $this->assigned->first_name . ' ' . $this->assigned->last_name . '.', $data['content']);
    }

    /** @test */
    public function getRecipients()
    {
        $users = $this->event->getRecipients();

        $this->assertSame(3, count($users));
        $this->assertSame($this->owner->id, $users[0]->id);
        $this->assertSame($this->admin->id, $users[1]->id);
        $this->assertSame($this->assigned->id, $users[2]->id);
    }

    /** @test */
    public function getType()
    {
        $this->assertSame(EventTypes::PROJECT_ASSIGNED, $this->event->getType());
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
