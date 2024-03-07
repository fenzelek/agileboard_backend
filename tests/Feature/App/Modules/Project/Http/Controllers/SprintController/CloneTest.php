<?php

namespace Tests\Feature\App\Modules\Project\Http\Controllers\SprintController;

use App\Models\Db\Role;
use App\Models\Db\Sprint;
use App\Models\Db\Ticket;
use App\Models\Other\RoleType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Illuminate\Support\Facades\DB;
use Tests\Feature\App\Modules\Project\Http\Controllers\ProjectController\CloneTest\TestTrait;
use Tests\TestCase;

class CloneTest extends TestCase
{
    use DatabaseTransactions, TestTrait;

    /** @var Carbon */
    private $now;

    /** @var Sprint  */
    private $sprint;

    protected function setUp():void
    {
        parent::setUp();

        $this->stories = collect();

        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $this->createCompany();
        $this->createBaseProject();
        $this->sprint = $this->project->sprints()->first();
    }

    /**
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::clone
     * @test
     */
    public function clone_success_as_admin()
    {
        $name = 'cloned sprint';
        $activated = true;
        $sprint_count = $this->project->sprints()->count();
        $task_count = $this->project->tickets()->count();

        $response = $this->cloneSprint($name, $activated);
        $response->assertJsonStructure(['data']);
        $data = $response->decodeResponseJson()['data'];
        $cloned_sprint_id = $data['id'];

        $response->assertStatus(201);
        $this->assertNotEmpty($cloned_sprint_id);
        $this->assertNotEquals($this->sprint->id, $cloned_sprint_id);

        $this->assertCount($sprint_count + 1, Sprint::all());
        $this->assertEquals($this->project->id, $data['project_id']);

        // check base project
        $this->assertDatabaseHas('sprints', [
            'id' => $cloned_sprint_id,
        ]);

        // check cloned project
        $this->assertDatabaseHas('sprints', [
            'id' => $cloned_sprint_id,
            'project_id' => $this->project->id,
            'name' => $name,
            'status' => Sprint::ACTIVE,
            'priority' => $this->sprint->priority,
            'planned_activation' => $this->sprint->planned_activation,
            'planned_closing' => $this->sprint->planned_closing,
            'activated_at' => $this->now,
            'created_at' => $this->now,
            'updated_at' => $this->now,
            'closed_at' => null,
        ]);

        $cloned_sprint = Sprint::find($cloned_sprint_id);
        $this->assertNotEquals($this->project->created_at, $cloned_sprint->created_at);
        $this->assertNotEquals($this->project->updated_at, $cloned_sprint->updated_at);
        $this->assertEquals($task_count * 2, $this->project->fresh()->created_tickets);
        $this->assertSame(1, DB::transactionLevel());
    }

    /**
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::clone
     * @depends clone_success_as_admin
     * @test
     */
    public function clone_success_as_owner()
    {
        $this->project->users()->detach($this->user);
        $this->project->users()->attach($this->user, [
            'role_id' => Role::findByName(RoleType::OWNER)->id,
        ]);

        $name = 'cloned sprint';
        $activated = true;
        $response = $this->cloneSprint($name, $activated);

        $response->assertStatus(201);
    }

    /**
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::clone
     * @test
     */
    public function clone_without_activation()
    {
        $name = 'cloned sprint';
        $activated = false;
        $sprint_count = $this->project->sprints()->count();

        $response = $this->cloneSprint($name, $activated);
        $response->assertJsonStructure(['data']);
        $data = $response->decodeResponseJson()['data'];
        $cloned_sprint_id = $data['id'];

        $response->assertStatus(201);
        $this->assertNotEmpty($cloned_sprint_id);
        $this->assertNotEquals($this->sprint->id, $cloned_sprint_id);

        $this->assertCount($sprint_count + 1, Sprint::all());
        $this->assertEquals($this->project->id, $data['project_id']);

        // check base project
        $this->assertDatabaseHas('sprints', [
            'id' => $cloned_sprint_id,
        ]);

        // check cloned project
        $this->assertDatabaseHas('sprints', [
            'id' => $cloned_sprint_id,
            'project_id' => $this->project->id,
            'name' => $name,
            'status' => Sprint::INACTIVE,
            'priority' => $this->sprint->priority,
            'planned_activation' => $this->sprint->planned_activation,
            'planned_closing' => $this->sprint->planned_closing,
            'activated_at' => null,
            'created_at' => $this->now,
            'updated_at' => $this->now,
            'closed_at' => null,
        ]);

        $cloned_sprint = Sprint::find($cloned_sprint_id);
        $this->assertNotEquals($this->project->created_at, $cloned_sprint->created_at);
        $this->assertNotEquals($this->project->updated_at, $cloned_sprint->updated_at);
        $this->assertSame(1, DB::transactionLevel());
    }

    /**
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::clone
     * @depends clone_success_as_admin
     * @test
     */
    public function clone_check_cloned_tickets_relation()
    {
        $response = $this->cloneSprint('cloned sprint', false);
        $cloned_sprint_id = $response->decodeResponseJson()['data']['id'];

        $cloned_sprint = Sprint::find($cloned_sprint_id);
        $this->assertGreaterThan(0, $this->sprint->tickets->count());
        $this->assertCount($this->sprint->tickets->count(), $cloned_sprint->tickets);

        // check tickets
        $this->checkClonedTicket($this->sprint->tickets[0], $cloned_sprint->tickets[0], 3);
        $this->checkClonedTicket($this->sprint->tickets[1], $cloned_sprint->tickets[1], 4);
    }

    /**
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::clone
     * @depends clone_success_as_admin
     * @test
     */
    public function clone_check_cloned_related_tickets()
    {
        $different_ticket = $this->subTicketFromDifferentSprint();

        $response = $this->cloneSprint('cloned sprint', false);
        $cloned_sprint_id = $response->decodeResponseJson()['data']['id'];

        $cloned_sprint = Sprint::find($cloned_sprint_id);
        $this->assertGreaterThan(0, $this->sprint->tickets->count());
        $this->assertCount($this->sprint->tickets->count(), $cloned_sprint->tickets);

        // check first ticket
        $this->assertEquals(1, $this->sprint->tickets[0]->subTickets()->count());
        $this->assertEquals(1, $cloned_sprint->tickets[0]->subTickets()->count());
        $this->assertEquals(
            $cloned_sprint->tickets[0]->subTickets()->first()->id,
            $cloned_sprint->tickets[1]->id
        );
        // check second ticket
        $this->assertEquals(1, $this->sprint->tickets[1]->subTickets()->count());
        $this->assertEquals(1, $cloned_sprint->tickets[1]->subTickets()->count());
        $this->assertEquals(
            $cloned_sprint->tickets[1]->subTickets()->first()->id,
            $different_ticket->id
        );
    }

    /**
     * @covers \App\Modules\Agile\Http\Controllers\SprintController::clone
     * @test
     */
    public function return_401_when_user_does_not_have_admin_or_owner_role()
    {
        $this->project->users()->detach($this->user);
        $this->project->users()->attach($this->user, [
            'role_id' => Role::findByName(RoleType::CLIENT)->id,
        ]);

        $response = $this->cloneSprint('cloned sprint', false);
        $response->assertStatus(401);
    }

    /**
     * @param string $name
     * @param bool $activated
     *
     * @return TestResponse
     */
    private function cloneSprint(string $name, bool $activated)
    {
        $this->mockTime('2019-07-08 12:12:12');
        $payload = compact('name', 'activated');

        return $this->postJson($this->getUrl(), $payload);
    }

    /**
     * @param string $time
     */
    private function mockTime(string $time): void
    {
        $this->now = Carbon::parse($time);
        Carbon::setTestNow($this->now);
    }

    /**
     * @return string
     */
    private function getUrl()
    {
        return route('sprints.clone', [
            'project' => $this->project->id,
            'sprint' => $this->sprint->id,
            'selected_company_id' => $this->company->id,
            ]);
    }

    /**
     * @param $source_ticket
     * @param $cloned_ticket
     * @param $expected_number
     */
    private function checkClonedTicket($source_ticket, $cloned_ticket, $expected_number)
    {
        $this->assertEquals($source_ticket->name, $cloned_ticket->name);
        $this->assertEquals($expected_number, explode('-', $cloned_ticket->title)[1]);
        $this->assertEquals($source_ticket->type_id, $cloned_ticket->type_id);
        $this->assertEquals($source_ticket->assigned_id, $cloned_ticket->assigned_id);
        $this->assertEquals($source_ticket->reporter_id, $cloned_ticket->reporter_id);
        $this->assertEquals($source_ticket->description, $cloned_ticket->description);
        $this->assertEquals($source_ticket->estimate_time, $cloned_ticket->estimate_time);
        $this->assertEquals($source_ticket->scheduled_time_start, $cloned_ticket->scheduled_time_start);
        $this->assertEquals($source_ticket->scheduled_time_end, $cloned_ticket->scheduled_time_end);
        $this->assertEquals($source_ticket->priority, $cloned_ticket->priority);
        $this->assertEquals($source_ticket->hidden, $cloned_ticket->hidden);

        $this->assertCount($source_ticket->comments->count(), $cloned_ticket->comments);
        $this->assertCount($source_ticket->stories->count(), $cloned_ticket->stories);
        $this->assertNotEquals($source_ticket->sprint->id, $cloned_ticket->sprint->id);
    }

    private function subTicketFromDifferentSprint(): Ticket
    {
        $another_sprint = $this->project->sprints()->create();
        $another_ticket = factory(Ticket::class)->create([
            'title' => 'TIC-900',
            'sprint_id' => $another_sprint->id,
            'status_id' => $this->project->statuses()->first()->id,
        ]);

        $this->sprint->tickets[1]->subTickets()->attach($another_ticket);

        return $another_ticket;
    }
}
