<?php

namespace Tests\Feature\App\Modules\Agile\Http\Controllers\TicketController;

use App\Helpers\ErrorCode;
use App\Models\Db\File;
use App\Models\Db\Integration\TimeTracking\User as TimeTrackingUser;
use App\Models\Db\Project;
use App\Models\Db\Ticket;
use App\Models\Db\TicketComment;
use App\Models\Db\TicketType;
use App\Models\Db\User;
use App\Models\Other\MorphMap;
use App\Models\Other\RoleType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BrowserKitTestCase;
use Tests\Helpers\ProjectHelper;
use Tests\Helpers\ResponseHelper;

class ShowTest extends BrowserKitTestCase
{
    use DatabaseTransactions, ProjectHelper, ResponseHelper, TestTrait, ShowTrait;

    /**
     * @scenario Showing a ticket
     * @suit Showing a ticket
     * @case Return error when user hasn't permission
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::show
     * @test
     */
    public function show_error_has_not_permission()
    {
        $this->initEnv();

        $project_2 = factory(Project::class)->create(['company_id' => $this->company->id]);
        $ticket = factory(Ticket::class)->create(['project_id' => $project_2->id]);
        $url = $this->prepareUrl($this->project->id, $ticket->id, $this->company->id);

        $this->get($url, []);
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /**
     * @scenario Showing a ticket
     * @suit Showing a ticket
     * @case Return error when ticket not exist
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::show
     * @test
     */
    public function show_error_ticket_not_exist()
    {
        $this->initEnv();
        $url = $this->prepareUrl($this->project->id, 0, $this->company->id);

        $this->get($url, []);
        $this->verifyErrorResponse(404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /**
     * @scenario Showing a ticket
     * @suit Showing a ticket
     * @case Showing by 'id'
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::show
     * @test
     */
    public function show_by_id_success_response()
    {
        $this->initEnv();

        $other_user = factory(User::class)->create();

        $parent_ticket = factory(Ticket::class)->create(['assigned_id' => $other_user->id]);
        $sub_ticket = factory(Ticket::class)->create(['assigned_id' => $other_user->id]);
        $ticket = factory(Ticket::class)->create([
            'project_id' => $this->project->id,
            'sprint_id' => $this->sprint->id,
            'status_id' => $this->status->id,
            'name' => 'test',
            'title' => 'test-2',
            'type_id' => TicketType::first()->id,
            'assigned_id' => $this->user->id,
            'reporter_id' => $other_user->id,
            'description' => 'descriptionsdfs',
            'estimate_time' => 123,
            'priority' => 2,
            'hidden' => 1,
        ]);
        $ticket->parentTickets()->attach($parent_ticket->id);
        $ticket->subTickets()->attach($sub_ticket->id);

        $url = $this->prepareUrl($this->project->id, $ticket->id, $this->company->id);

        $ticket->stories()->attach($this->story);
        $file = factory(File::class)->create();
        $ticket->files()->attach($file);
        $comment = factory(TicketComment::class)->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
        ]);

        $this->get($url)
            ->seeStatusCode(200);

        $response_ticket = $this->decodeResponseJson()['data'];

        $this->assertSame($ticket->id, $response_ticket['id']);
        $this->assertSame($ticket->project_id, $response_ticket['project_id']);
        $this->assertSame($ticket->sprint_id, $response_ticket['sprint_id']);
        $this->assertSame($ticket->status_id, $response_ticket['status_id']);
        $this->assertSame($ticket->name, $response_ticket['name']);
        $this->assertSame($ticket->title, $response_ticket['title']);
        $this->assertSame($ticket->type_id, $response_ticket['type_id']);
        $this->assertSame($ticket->assigned_id, $response_ticket['assigned_id']);
        $this->assertSame($ticket->reporter_id, $response_ticket['reporter_id']);
        $this->assertSame($ticket->description, $response_ticket['description']);
        $this->assertSame($ticket->estimate_time, $response_ticket['estimate_time']);
        $this->assertSame($ticket->priority, $response_ticket['priority']);
        $this->assertSame($ticket->hidden, $response_ticket['hidden']);
        $this->assertSame($this->now->toDateTimeString(), $response_ticket['created_at']);
        $this->assertSame($this->now->toDateTimeString(), $response_ticket['updated_at']);
        $this->assertSame(null, $response_ticket['deleted_at']);
        $this->assertSame(TicketType::first()->name, $response_ticket['type']['data']['name']);
        $this->assertSame($this->status->id, $response_ticket['status']['data']['id']);
        $this->assertSame($this->status->name, $response_ticket['status']['data']['name']);
        $this->assertSame($this->sprint->id, $response_ticket['sprint']['data']['id']);
        $this->assertSame($this->sprint->name, $response_ticket['sprint']['data']['name']);
        $this->assertSame(1, count($response_ticket['stories']['data']));
        $this->assertSame($this->story->id, $response_ticket['stories']['data'][0]['id']);
        $this->assertSame(1, count($response_ticket['files']['data']));
        $this->assertSame($file->id, $response_ticket['files']['data'][0]['id']);
        $this->assertSame(1, count($response_ticket['comments']['data']));
        $this->assertSame($comment->id, $response_ticket['comments']['data'][0]['id']);
        $this->assertSame(
            $comment->user_id,
            $response_ticket['comments']['data'][0]['user']['data']['id']
        );
        $this->assertSame(
            $this->getExpectedUserResponse($this->user),
            $response_ticket['assigned_user']['data']
        );
        $this->assertSame(
            $this->getExpectedUserResponse($other_user),
            $response_ticket['reporting_user']['data']
        );

        // check parent tickets
        $this->assertSame([$parent_ticket->id], $response_ticket['parent_ticket_ids']);
        $this->assertSame($parent_ticket->id, $response_ticket['parent_tickets']['data'][0]['id']);
        $this->assertSame($parent_ticket->name,
            $response_ticket['parent_tickets']['data'][0]['name']);
        $this->assertSame($other_user->id,
            $response_ticket['parent_tickets']['data'][0]['assigned_user']['data']['id']);
        $this->assertSame($other_user->email,
            $response_ticket['parent_tickets']['data'][0]['assigned_user']['data']['email']);
        $this->assertSame($other_user->first_name,
            $response_ticket['parent_tickets']['data'][0]['assigned_user']['data']['first_name']);
        $this->assertSame($other_user->last_name,
            $response_ticket['parent_tickets']['data'][0]['assigned_user']['data']['last_name']);
        $this->assertEquals($other_user->avatar,
            $response_ticket['parent_tickets']['data'][0]['assigned_user']['data']['avatar']);

        // check sub-tickets
        $this->assertSame([$sub_ticket->id], $response_ticket['sub_ticket_ids']);
        $this->assertSame($sub_ticket->id, $response_ticket['sub_tickets']['data'][0]['id']);
        $this->assertSame($sub_ticket->name, $response_ticket['sub_tickets']['data'][0]['name']);
        $this->assertSame($other_user->id,
            $response_ticket['sub_tickets']['data'][0]['assigned_user']['data']['id']);
        $this->assertSame($other_user->email,
            $response_ticket['sub_tickets']['data'][0]['assigned_user']['data']['email']);
        $this->assertSame($other_user->first_name,
            $response_ticket['sub_tickets']['data'][0]['assigned_user']['data']['first_name']);
        $this->assertSame($other_user->last_name,
            $response_ticket['sub_tickets']['data'][0]['assigned_user']['data']['last_name']);
        $this->assertEquals($other_user->avatar,
            $response_ticket['sub_tickets']['data'][0]['assigned_user']['data']['avatar']);
    }

    /**
     * @scenario Showing a ticket
     * @suit Showing a ticket
     * @case Showing by 'title'
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::show
     * @test
     */
    public function show_by_title_success_response()
    {
        $this->initEnv();

        $other_user = factory(User::class)->create();
        $ticket2 = factory(Ticket::class)->create([
            'project_id' => $this->project->id,
            'sprint_id' => $this->sprint->id,
            'status_id' => $this->status->id,
            'name' => 'test',
            'title' => 'test-2',
            'type_id' => TicketType::first()->id,
            'assigned_id' => $this->user->id,
            'reporter_id' => $other_user->id,
            'description' => 'descriptionsdfs',
            'estimate_time' => 123,
            'priority' => 2,
            'hidden' => 1,
        ]);
        $ticket2->stories()->attach($this->story);
        $file = factory(File::class)->create();
        $ticket2->files()->attach($file);
        $comment = factory(TicketComment::class)->create([
            'ticket_id' => $ticket2->id,
            'user_id' => $this->user->id,
        ]);

        $this->get('/projects/' . $this->project->id . '/tickets/' . $ticket2->title .
            '?selected_company_id=' . $this->company->id)
            ->seeStatusCode(200);

        $response_ticket = $this->decodeResponseJson()['data'];

        $this->assertSame($ticket2->id, $response_ticket['id']);
        $this->assertSame($ticket2->project_id, $response_ticket['project_id']);
        $this->assertSame($ticket2->sprint_id, $response_ticket['sprint_id']);
        $this->assertSame($ticket2->status_id, $response_ticket['status_id']);
        $this->assertSame($ticket2->name, $response_ticket['name']);
        $this->assertSame($ticket2->title, $response_ticket['title']);
        $this->assertSame($ticket2->type_id, $response_ticket['type_id']);
        $this->assertSame($ticket2->assigned_id, $response_ticket['assigned_id']);
        $this->assertSame($ticket2->reporter_id, $response_ticket['reporter_id']);
        $this->assertSame($ticket2->description, $response_ticket['description']);
        $this->assertSame($ticket2->estimate_time, $response_ticket['estimate_time']);
        $this->assertSame($ticket2->priority, $response_ticket['priority']);
        $this->assertSame($ticket2->hidden, $response_ticket['hidden']);
        $this->assertSame($this->now->toDateTimeString(), $response_ticket['created_at']);
        $this->assertSame($this->now->toDateTimeString(), $response_ticket['updated_at']);
        $this->assertSame(null, $response_ticket['deleted_at']);
        $this->assertSame(TicketType::first()->name, $response_ticket['type']['data']['name']);
        $this->assertSame($this->status->id, $response_ticket['status']['data']['id']);
        $this->assertSame($this->status->name, $response_ticket['status']['data']['name']);
        $this->assertSame($this->sprint->id, $response_ticket['sprint']['data']['id']);
        $this->assertSame($this->sprint->name, $response_ticket['sprint']['data']['name']);
        $this->assertSame(1, count($response_ticket['stories']['data']));
        $this->assertSame($this->story->id, $response_ticket['stories']['data'][0]['id']);
        $this->assertSame(1, count($response_ticket['files']['data']));
        $this->assertSame($file->id, $response_ticket['files']['data'][0]['id']);
        $this->assertSame(1, count($response_ticket['comments']['data']));
        $this->assertSame($comment->id, $response_ticket['comments']['data'][0]['id']);
        $this->assertSame(
            $comment->user_id,
            $response_ticket['comments']['data'][0]['user']['data']['id']
        );
        $this->assertSame(
            $this->getExpectedUserResponse($this->user),
            $response_ticket['assigned_user']['data']
        );
        $this->assertSame(
            $this->getExpectedUserResponse($other_user),
            $response_ticket['reporting_user']['data']
        );
    }

    /**
     * @scenario Showing a ticket
     * @suit Showing a ticket
     * @case Showing all users time tracking for admin
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::show
     * @test
     */
    public function show_it_shows_all_users_time_tracking_for_admin()
    {
        $this->initEnv();
        $this->verifyTimeTrackingEntriesForRole(RoleType::ADMIN);
    }

    /**
     * @scenario Showing a ticket
     * @suit Showing a ticket
     * @case Showing all users time tracking for developer
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::show
     * @test
     */
    public function show_it_shows_all_users_time_tracking_when_developer()
    {
        $this->initEnv();
        $this->verifyTimeTrackingEntriesForRole(RoleType::DEVELOPER);
    }

    /**
     * @scenario Showing a ticket
     * @suit Showing a ticket
     * @case Showing all users time tracking for client
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::show
     * @test
     */
    public function show_it_shows_all_users_time_tracking_when_client()
    {
        $this->initEnv();
        $this->verifyTimeTrackingEntriesForRole(RoleType::CLIENT);
    }

    /**
     * @scenario Showing a ticket
     * @suit Showing a ticket
     * @case Not showing any time tracking for client
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::show
     * @test
     */
    public function show_it_doesnt_shows_any_time_tracking_when_client()
    {
        $this->initEnv();

        $this->setProjectRole($this->project, RoleType::CLIENT);

        $ticket = factory(Ticket::class)->create([
            'project_id' => $this->project->id,
        ]);

        $users = factory(User::class, 5)->create();
        $tracking_users = factory(TimeTrackingUser::class, 5)->create(['user_id' => null]);
        $this->createTimeTrackingActivities($tracking_users, $users, $ticket);
        $url = $this->prepareUrl($this->project->id, $ticket->id, $this->company->id);

        $this->get($url)
            ->seeStatusCode(200);
        $response_ticket = $this->decodeResponseJson()['data'];

        $this->assertSame($ticket->id, $response_ticket['id']);

        $this->assertArrayNotHasKey('time_tracking_summary', $response_ticket);
        $this->assertArrayNotHasKey('stats', $response_ticket);
    }

    /**
     * @scenario Showing a ticket
     * @suit Showing a ticket
     * @case Show empty array for developer if no time tracking entries created
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::show
     * @test
     */
    public function show_it_will_show_empty_array_for_developer_if_no_time_tracking_entries_created()
    {
        $this->initEnv();

        $this->setProjectRole($this->project, RoleType::DEVELOPER);

        $ticket = factory(Ticket::class)->create([
            'project_id' => $this->project->id,
        ]);
        $url = $this->prepareUrl($this->project->id, $ticket->id, $this->company->id);

        $this->get($url)->seeStatusCode(200);
        $response_ticket = $this->decodeResponseJson()['data'];

        $this->assertSame($ticket->id, $response_ticket['id']);
        $this->assertEquals([], $response_ticket['time_tracking_summary']['data']);
    }

    /**
     * @feature Involved
     * @scenario Show involved user list
     * @case Involved list contains two position
     * @expectation Return valid two items list
     * @test
     */
    public function show_involvedListContainsTwoPosition()
    {
        $user = $this->createAndBeUser();

        $company = $this->createCompany();
        $project = $this->createNewProject($company);
        $this->setProjectRole($project, RoleType::CLIENT, $user);

        $ticket = factory(Ticket::class)->create([
            'project_id' => $project->id,
        ]);

        $user_1 = $this->createNewUser([
            'first_name' => 'test first name 1',
            'last_name' => 'test last name 1',
            'avatar' => 'avatar 1',
        ]);

        $user_2 = $this->createNewUser([
            'first_name' => 'test first name 2',
            'last_name' => 'test last name 2',
            'avatar' => 'avatar 2',
        ]);

        $this->createInvolved([
            'user_id' => $user_1->id,
            'company_id' => $company->getCompanyId(),
            'project_id' => $project->id,
            'source_id' => $ticket->id,
            'source_type' => MorphMap::TICKETS,
        ]);

        $this->createInvolved([
            'user_id' => $user_2->id,
            'company_id' => $company->getCompanyId(),
            'project_id' => $project->id,
            'source_id' => $ticket->id,
            'source_type' => MorphMap::TICKETS,
        ]);

        $url = $this->prepareUrl($project->id, $ticket->id, $company->getCompanyId());

        //WHEN
        $this->get($url);

        //THEN
        $this->seeStatusCode(200);
        $involved = $this->decodeResponseJson()['data']['involved']['data'];

        $this->assertEquals($user_1->id, $involved[0]['user_id']);
        $this->assertSame('test first name 1', $involved[0]['first_name']);
        $this->assertSame('test last name 1', $involved[0]['last_name']);
        $this->assertSame('avatar 1', $involved[0]['avatar']);

        $this->assertEquals($user_2->id, $involved[1]['user_id']);
        $this->assertSame('test first name 2', $involved[1]['first_name']);
        $this->assertSame('test last name 2', $involved[1]['last_name']);
        $this->assertSame('avatar 2', $involved[1]['avatar']);
    }
}
