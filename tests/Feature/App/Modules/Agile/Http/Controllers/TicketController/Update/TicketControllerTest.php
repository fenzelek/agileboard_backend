<?php

namespace Tests\Feature\App\Modules\Agile\Http\Controllers\TicketController\Update;

use App\Helpers\ErrorCode;
use App\Models\Db\History;
use App\Models\Db\Interaction;
use App\Models\Db\InteractionPing;
use App\Models\Db\Involved;
use App\Models\Db\Project;
use App\Models\Db\Story;
use App\Models\Db\Ticket;
use App\Models\Db\TicketRealization;
use App\Models\Db\TicketType;
use App\Models\Db\User;
use App\Models\Other\Interaction\ActionType;
use App\Models\Other\Interaction\InteractionEventType;
use App\Models\Other\Interaction\NotifiableGroupType;
use App\Models\Other\Interaction\NotifiableType;
use App\Models\Other\Interaction\SourceType;
use App\Models\Other\MorphMap;
use App\Models\Other\RoleType;
use App\Modules\Agile\Events\UpdateTicketEvent;
use App\Modules\Notification\Models\DatabaseNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Tests\BrowserKitTestCase;
use Tests\Feature\App\Modules\Agile\Http\Controllers\TicketController\TestTrait;
use Tests\Helpers\ProjectHelper;
use Tests\Helpers\ResponseHelper;

class TicketControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions;
    use ProjectHelper;
    use ResponseHelper;
    use TestTrait;
    use TicketControllerTrait;

    /**
     * @scenario Ticket Updating
     * @suit Ticket Updating
     * @case Return error when user hasn't required role
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::update
     * @test
     */
    public function update_return_401_when_update_without_required_role()
    {
        $this->initEnv(RoleType::ADMIN);

        $this->project->permission->ticket_update = [
            'roles' => [
                ['name' => 'owner', 'value' => true],
            ],
        ];
        $this->project->permission->save();
        $ticket = $this->createTicket($this->project->id, 1);
        $url = $this->prepareUrl($this->project->id, $ticket->id, $this->company->id);

        $this->put($url, []);

        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /**
     * @scenario Ticket Updating
     * @suit Ticket Updating
     * @case Return error when user hasn't permission
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::update
     * @test
     */
    public function update_error_has_not_permission()
    {
        $this->initEnv();

        $project_2 = factory(Project::class)->create(['company_id' => $this->company->id]);
        $ticket = factory(Ticket::class)->create(['project_id' => $project_2->id]);
        $url = $this->prepareUrl($this->project->id, $ticket->id, $this->company->id);

        $this->put($url, []);
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /**
     * @scenario Ticket Updating
     * @suit Ticket Updating
     * @case Return error when ticket not exist
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::update
     * @test
     */
    public function update_error_ticket_not_exist()
    {
        $this->initEnv();
        $url = $this->prepareUrl($this->project->id, 0, $this->company->id);

        $this->put($url, []);
        $this->verifyErrorResponse(404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /**
     * @scenario Ticket Updating
     * @suit Ticket Updating
     * @case Check data in database after success updating
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::update
     * @test
     */
    public function update_success_db()
    {
        $this->mockInteractionNotificationManager();
        $this->initEnv();

        $parent_ticket = factory(Ticket::class)->create();
        $sub_ticket = factory(Ticket::class)->create();

        $old_type = TicketType::orderBy('id', 'desc')->first();
        $new_type = TicketType::first()->first();
        $ticket2 = factory(Ticket::class)->create([
            'project_id' => $this->project->id,
            'sprint_id' => 0,
            'status_id' => $this->status->id,
            'type_id' => $old_type->id,
            'priority' => 2,
            'scheduled_time_start' => '2018-11-11 11:11:11',
            'scheduled_time_end' => '2018-11-12 11:11:11',
            'hidden' => true,
        ]);
        $ticket2->stories()->attach($this->story);
        $story2 = factory(Story::class)->create(['project_id' => $this->project->id]);
        $url = $this->prepareUrl($this->project->id, $ticket2->id, $this->company->id);

        $send_data = [
            'parent_ticket_ids' => [$parent_ticket->id],
            'sub_ticket_ids' => [$sub_ticket->id],
            'name' => ' test ',
            'sprint_id' => $this->sprint->id,
            'type_id' => $new_type->id,
            'assigned_id' => $this->user->id,
            'reporter_id' => $this->user->id,
            'description' => ' description ',
            'estimate_time' => 123,
            'scheduled_time_start' => '2017-10-10 10:10:10',
            'scheduled_time_end' => '2017-10-11 10:10:10',
            'story_id' => [$story2->id],
        ];

        $ticket_realization = TicketRealization::create([
            'ticket_id' => $ticket2->id,
            'user_id' => factory(User::class)->create()->id,
            'start_at' => Carbon::now(),
            'end_at' => null,
        ]);

        $before_tickets = Ticket::count();
        $before_ticket_realizations = TicketRealization::count();

        History::whereRaw('1 = 1')->delete();

        $this->put($url, $send_data)
            ->seeStatusCode(200);

        $this->assertEquals($before_tickets, Ticket::count());

        $ticket = $ticket2->fresh();
        $this->assertSame($ticket2->id, $ticket->id);
        $this->assertSame($ticket2->project_id, $ticket->project_id);
        $this->assertSame($send_data['sprint_id'], $ticket->sprint_id);
        $this->assertSame($ticket2->status_id, $ticket->status_id);
        $this->assertSame('test', $ticket->name);
        $this->assertSame($ticket2->title, $ticket->title);
        $this->assertSame($send_data['type_id'], $ticket->type_id);
        $this->assertSame($send_data['assigned_id'], $ticket->assigned_id);
        $this->assertSame($send_data['reporter_id'], $ticket->reporter_id);
        $this->assertSame('description', $ticket->description);
        $this->assertSame($send_data['estimate_time'], $ticket->estimate_time);
        $this->assertSame($send_data['scheduled_time_start'],
            $ticket->scheduled_time_start->toDateTimeString());
        $this->assertSame($send_data['scheduled_time_end'],
            $ticket->scheduled_time_end->toDateTimeString());
        $this->assertSame($ticket2->priority, $ticket->priority);
        $this->assertSame(1, $ticket->hidden);
        $this->assertSame(
            $this->now->toDateTimeString(),
            $ticket->created_at->toDateTimeString()
        );
        $this->assertSame(
            $this->now->toDateTimeString(),
            $ticket->updated_at->toDateTimeString()
        );
        $this->assertSame(1, count($ticket->stories));
        $this->assertSame($story2->id, $ticket->stories[0]->id);

        //history
        $this->assertSame(9, History::where([
            'user_id' => $this->user->id,
            'resource_id' => $ticket->id,
            'object_id' => $ticket->id,
            'created_at' => $this->now->toDateTimeString(),
        ])->count());

        $this->same_history(
            'name',
            $ticket2->name,
            null,
            $ticket->name,
            null
        );

        $this->same_history(
            'sprint_id',
            0,
            'Backlog',
            $this->sprint->id,
            $this->sprint->name
        );

        $this->same_history(
            'type_id',
            $old_type->id,
            $old_type->name,
            $new_type->id,
            $new_type->name
        );

        $this->same_history(
            'assigned_id',
            0,
            null,
            $this->user->id,
            $this->user->first_name . ' ' . $this->user->last_name
        );

        $this->same_history(
            'reporter_id',
            0,
            null,
            $this->user->id,
            $this->user->first_name . ' ' . $this->user->last_name
        );

        $this->same_history(
            'description',
            $ticket2->description,
            null,
            $ticket->description,
            null
        );

        $this->same_history(
            'estimate_time',
            $ticket2->estimate_time,
            null,
            $ticket->estimate_time,
            null
        );

        $this->same_history(
            'story_id',
            null,
            null,
            $ticket->stories[0]->id,
            $ticket->stories[0]->name
        );

        $this->same_history(
            'story_id',
            $this->story->id,
            $this->story->name,
            null,
            null
        );

        $this->assertEquals($before_ticket_realizations + 1, TicketRealization::count());
        $this->assertSame(1, TicketRealization::where([
            'ticket_id' => $ticket2->id,
            'user_id' => $this->user->id,
            'start_at' => $this->now->toDateTimeString(),
            'end_at' => null,
        ])->count());

        $ticket_realization = $ticket_realization->fresh();
        $this->assertTrue($ticket_realization->end_at != null);

        // check parent tickets
        $this->assertCount(1, $ticket->parentTickets);
        $this->assertContains($parent_ticket->id, $ticket->parentTickets->pluck('id')->toArray());

        // check sub-tickets
        $this->assertCount(1, $ticket->subTickets);
        $this->assertContains($sub_ticket->id, $ticket->subTickets->pluck('id')->toArray());
    }

    /**
     * @scenario Ticket Updating
     * @suit Ticket Updating
     * @case Success response
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::update
     * @test
     */
    public function update_success_response()
    {
        $this->mockInteractionNotificationManager();

        Event::fake();
        $this->initEnv();
        // manually creating permissions because event is fake
        $this->createPermissions();

        $ticket2 = factory(Ticket::class)->create([
            'project_id' => $this->project->id,
            'type_id' => TicketType::orderBy('id', 'desc')->first()->id,
            'priority' => 2,
            'hidden' => true,
            'scheduled_time_start' => '2018-11-11 11:11:11',
            'scheduled_time_end' => '2018-11-12 11:11:11',
        ]);
        $send_data = [
            'name' => ' test ',
            'sprint_id' => $this->sprint->id,
            'type_id' => TicketType::first()->id,
            'assigned_id' => $this->user->id,
            'reporter_id' => $this->user->id,
            'description' => ' description ',
            'estimate_time' => 123,
            'scheduled_time_start' => '2017-10-10 10:10:10',
            'scheduled_time_end' => '2017-10-11 10:10:10',
        ];
        $url = $this->prepareUrl($this->project->id, $ticket2->id, $this->company->id);

        $this->put($url, $send_data)
            ->seeStatusCode(200);

        Event::assertDispatched(UpdateTicketEvent::class, function ($e) use ($ticket2) {
            if (
                $e->project->id == $this->project->id &&
                $e->sprint_old_id == $ticket2->sprint_id &&
                $e->sprint_new_id == $this->sprint->id &&
                $e->ticket->id == $ticket2->id) {
                return true;
            }
        });

        $response_ticket = $this->decodeResponseJson()['data'];
        $this->assertSame($ticket2->id, $response_ticket['id']);
        $this->assertSame($ticket2->project_id, $response_ticket['project_id']);
        $this->assertSame($send_data['sprint_id'], $response_ticket['sprint_id']);
        $this->assertSame($ticket2->status_id, $response_ticket['status_id']);
        $this->assertSame('test', $response_ticket['name']);
        $this->assertSame($send_data['type_id'], $response_ticket['type_id']);
        $this->assertSame($send_data['assigned_id'], $response_ticket['assigned_id']);
        $this->assertSame($send_data['reporter_id'], $response_ticket['reporter_id']);
        $this->assertSame('description', $response_ticket['description']);
        $this->assertSame($send_data['estimate_time'], $response_ticket['estimate_time']);
        $this->assertSame($send_data['scheduled_time_start'],
            $response_ticket['scheduled_time_start']);
        $this->assertSame($send_data['scheduled_time_end'], $response_ticket['scheduled_time_end']);
        $this->assertSame($ticket2->priority, $response_ticket['priority']);
        $this->assertSame(1, $response_ticket['hidden']);
        $this->assertSame($this->now->toDateTimeString(), $response_ticket['created_at']);
        $this->assertSame($this->now->toDateTimeString(), $response_ticket['updated_at']);
    }

    /**
     * @scenario Ticket Updating
     * @suit Ticket Updating
     * @case Success response when reporter user was deleted from company
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::update
     * @test
     */
    public function update_success_reporter_user_deleted_from_company()
    {
        $this->mockInteractionNotificationManager();
        Event::fake();
        $this->initEnv();
        // manually creating permissions because event is fake
        $this->createPermissions();

        $other_user_in_company = $this->createUserCompany(RoleType::ADMIN);
        $ticket2 = factory(Ticket::class)->create([
            'project_id' => $this->project->id,
            'type_id' => TicketType::orderBy('id', 'desc')->first()->id,
            'priority' => 2,
            'hidden' => true,
            'scheduled_time_start' => '2018-11-11 11:11:11',
            'scheduled_time_end' => '2018-11-12 11:11:11',
        ]);
        $send_data = [
            'name' => ' test ',
            'sprint_id' => $this->sprint->id,
            'type_id' => TicketType::first()->id,
            'assigned_id' => $other_user_in_company->id,
            'reporter_id' => $other_user_in_company->id,
            'description' => ' description ',
            'estimate_time' => 123,
            'scheduled_time_start' => '2017-10-10 10:10:10',
            'scheduled_time_end' => '2017-10-11 10:10:10',
        ];
        $url = $this->prepareUrl($this->project->id, $ticket2->id, $this->company->id);

        $this->put($url, $send_data)
            ->seeStatusCode(200);
    }

    /**
     * @feature Ticket
     * @scenario Update ticket
     * @case Ticket with single interaction
     *
     * @Expectation Success save to db
     *
     * @dataProvider validSingleUserInteractionData
     *
     * @test
     */
    public function update_ticketWithSingleInteraction(array $entry_interaction_data): void
    {
        //GIVEN
        $this->mockInteractionNotificationManager();
        $recipient = $this->createNewUser();

        $this->user = $this->createNewUser();
        auth()->loginUsingId($this->user->id);

        $this->company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = $this->createNewProject($this->company->id);
        $ticket = $this->createNewTicket($project->id);

        $entry_interaction_data[0] = array_merge($entry_interaction_data[0], ['recipient_id' => $recipient->id]);

        $data_send = $this->getDataSendSimple();
        $data_send['interactions'] = [
            'data' => $entry_interaction_data,
        ];

        $url = $this->prepareUrl($project->id, $ticket->id, $this->company->id);

        //WHEN
        $this->put($url, $data_send);

        //THEN
        $this->assertResponseStatus(200);

        /** @var Ticket $ticket */
        $ticket = Ticket::first();

        $this->assertCount(1, Interaction::all());
        /** @var Interaction $interaction */
        $interaction = Interaction::first();
        $this->assertEquals($this->user->id , $interaction->user_id);
        $this->assertEquals($project->id, $interaction->project_id);
        $this->assertEquals($this->company->id, $interaction->company_id);
        $this->assertEquals(InteractionEventType::TICKET_EDIT, $interaction->event_type);
        $this->assertEquals(ActionType::PING, $interaction->action_type);
        $this->assertEquals($ticket->id, $interaction->source_id);
        $this->assertEquals('tickets', $interaction->source_type);

        $this->assertCount(1, InteractionPing::all());
        /** @var InteractionPing $interaction_ping */
        $interaction_ping = InteractionPing::first();
        $this->assertEquals($recipient->id , $interaction_ping->recipient_id);
        $this->assertEquals('label test', $interaction_ping->ref);
        $this->assertEquals(NotifiableType::USER, $interaction_ping->notifiable);
        $this->assertEquals('message test', $interaction_ping->message);
        $this->assertEquals($interaction->id, $interaction_ping->interaction_id);
    }

    /**
     * @feature Ticket
     * @scenario Update Ticket
     * @case Ticket with two interaction
     *
     * @Expectation Success save to db
     *
     * @dataProvider validTwoUserInteractionData
     *
     * @test
     */
    public function update_ticketWithTwoInteraction(array $entry_interaction_data): void
    {
        //GIVEN
        $this->mockInteractionNotificationManager();

        $this->user = $this->createNewUser();
        auth()->loginUsingId($this->user->id);

        $this->company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = $this->createNewProject($this->company->id);

        $recipient_1 = $this->createNewUser();
        $recipient_2 = $this->createNewUser();

        $ticket = $this->createNewTicket($project->id);

        $entry_interaction_data[0] = array_merge($entry_interaction_data[0], ['recipient_id' => $recipient_1->id]);
        $entry_interaction_data[1] = array_merge($entry_interaction_data[1], ['recipient_id' => $recipient_2->id]);

        $data_send = $this->getDataSendSimple();
        $data_send['interactions'] = [
            'data' => $entry_interaction_data,
        ];

        $url = $this->prepareUrl($project->id, $ticket->id, $this->company->id);

        //WHEN
        $this->put($url, $data_send);

        //THEN
        $this->assertResponseStatus(200);

        $this->assertCount(1, Interaction::all());
        /** @var Interaction $interaction */
        $interaction = Interaction::first();

        $this->assertCount(2, InteractionPing::all());
        /** @var InteractionPing $interaction_ping_1 */
        $interaction_ping_1 = InteractionPing::first();
        $this->assertEquals($recipient_1->id , $interaction_ping_1->recipient_id);
        $this->assertEquals('label test 1', $interaction_ping_1->ref);
        $this->assertEquals(NotifiableType::USER, $interaction_ping_1->notifiable);
        $this->assertEquals('message test 1', $interaction_ping_1->message);
        $this->assertEquals($interaction->id, $interaction_ping_1->interaction_id);

        /** @var InteractionPing $interaction_ping_2 */
        $interaction_ping_2 = InteractionPing::all()->last();
        $this->assertEquals($recipient_2->id , $interaction_ping_2->recipient_id);
        $this->assertEquals('label test 2', $interaction_ping_2->ref);
        $this->assertEquals(NotifiableType::USER, $interaction_ping_2->notifiable);
        $this->assertEquals('message test 2', $interaction_ping_2->message);
        $this->assertEquals($interaction->id, $interaction_ping_2->interaction_id);
    }

    /**
     * @feature Ticket
     * @scenario Update ticket
     * @case Ticket with group interaction
     *
     * @Expectation Success save to db
     *
     * @dataProvider validGroupInteractionData
     *
     * @test
     */
    public function update_ticketWithGroupInteraction(array $entry_interaction_data): void
    {
        //GIVEN
        $this->mockInteractionNotificationManager();

        $this->user = $this->createNewUser();
        auth()->loginUsingId($this->user->id);

        $this->company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = $this->createNewProject($this->company->id);

        $ticket = $this->createNewTicket($project->id);

        $entry_interaction_data[0] = array_merge($entry_interaction_data[0], ['recipient_id' => NotifiableGroupType::ALL]);

        $data_send = $this->getDataSendSimple();
        $data_send['interactions'] = [
            'data' => $entry_interaction_data,
        ];

        $url = $this->prepareUrl($project->id, $ticket->id, $this->company->id);

        //WHEN
        $this->put($url, $data_send);

        //THEN
        $this->assertResponseStatus(200);

        /** @var Ticket $ticket */
        $ticket = Ticket::first();

        $this->assertCount(1, Interaction::all());
        /** @var Interaction $interaction */
        $interaction = Interaction::first();
        $this->assertEquals($this->user->id , $interaction->user_id);
        $this->assertEquals($project->id, $interaction->project_id);
        $this->assertEquals(InteractionEventType::TICKET_EDIT, $interaction->event_type);
        $this->assertEquals(ActionType::PING, $interaction->action_type);
        $this->assertEquals($ticket->id, $interaction->source_id);
        $this->assertEquals('tickets', $interaction->source_type);

        $this->assertCount(1, InteractionPing::all());
        /** @var InteractionPing $interaction_ping */
        $interaction_ping = InteractionPing::first();
        $this->assertEquals(NotifiableGroupType::ALL , $interaction_ping->recipient_id);
        $this->assertEquals('label test', $interaction_ping->ref);
        $this->assertEquals(NotifiableType::GROUP, $interaction_ping->notifiable);
        $this->assertEquals('message test', $interaction_ping->message);
        $this->assertEquals($interaction->id, $interaction_ping->interaction_id);
    }

    /**
     * @feature Ticket
     * @scenario Update ticket
     * @case Ticket with mixed type interaction
     *
     * @Expectation Success save to db
     *
     * @dataProvider validMixedInteractionData
     * @test
     */
    public function update_ticketWithMixedTypeInteraction($entry_interaction_data): void
    {
        //GIVEN
        $this->mockInteractionNotificationManager();

        $this->user = $this->createNewUser();
        auth()->loginUsingId($this->user->id);

        $recipient_1 = $this->createNewUser();

        $this->company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = $this->createNewProject($this->company->id);

        $ticket = $this->createNewTicket($project->id);

        $entry_interaction_data[0] = array_merge($entry_interaction_data[0], ['recipient_id' => $recipient_1->id]);
        $entry_interaction_data[1] = array_merge($entry_interaction_data[1], ['recipient_id' => NotifiableGroupType::ALL]);

        $data_send = $this->getDataSendSimple();
        $data_send['interactions'] = [
            'data' => $entry_interaction_data,
        ];

        $url = $this->prepareUrl($project->id, $ticket->id, $this->company->id);

        //WHEN
        $this->put($url, $data_send);

        //THEN
        $this->assertResponseStatus(200);

        $this->assertCount(1, Interaction::all());
        /** @var Interaction $interaction */
        $interaction = Interaction::first();

        $this->assertCount(2, InteractionPing::all());
        /** @var InteractionPing $interaction_ping_1 */
        $interaction_ping_1 = InteractionPing::first();
        $this->assertEquals($recipient_1->id , $interaction_ping_1->recipient_id);
        $this->assertEquals('label test 1', $interaction_ping_1->ref);
        $this->assertEquals(NotifiableType::USER, $interaction_ping_1->notifiable);
        $this->assertEquals('message test 1', $interaction_ping_1->message);
        $this->assertEquals($interaction->id, $interaction_ping_1->interaction_id);

        /** @var InteractionPing $interaction_ping_2 */
        $interaction_ping_2 = InteractionPing::all()->last();
        $this->assertEquals(NotifiableGroupType::ALL , $interaction_ping_2->recipient_id);
        $this->assertEquals('label test 2', $interaction_ping_2->ref);
        $this->assertEquals(NotifiableType::GROUP, $interaction_ping_2->notifiable);
        $this->assertEquals('message test 2', $interaction_ping_2->message);
        $this->assertEquals($interaction->id, $interaction_ping_2->interaction_id);
    }

    /**
     * @feature Involved
     * @scenario Update ticket with involved list
     * @case Involved list contains two position
     * @expectation Involved list contains only new positions, Notification contains new positions
     * @test
     */
    public function update_involvedListContainsTwoPosition()
    {
        //GIVEN
        $user = $this->createAndBeUser();

        $company = $this->createCompany();
        $project = $this->createNewProject($company->getCompanyId());
        $this->setProjectRole($project, RoleType::ADMIN, $user);

        $ticket_type = TicketType::orderBy('id', 'desc')->first();
        $ticket = factory(Ticket::class)->create([
            'project_id' => $project->id,
            'type_id' => $ticket_type->id,
        ]);

        $user_1 = $this->createNewUser();
        $user_2 = $this->createNewUser();

        $user_1->projects()->save($project);
        $user_2->projects()->save($project);

        $data_send = [
            'name' => ' test ',
            'sprint_id' => 0,
            'type_id' => $ticket_type->id,
            'estimate_time' => 0,
            'involved_ids' => [
                $user_1->id,
                $user_2->id,
            ],
        ];

        $url = $this->prepareUrl($project->id, $ticket->id, $company->id);

        //WHEN
        $this->put($url, $data_send);

        //THEN
        $this->seeStatusCode(200);
        $this->assertCount(2, Involved::all());

        /** @var Involved $involved_first */
        $involved_first = Involved::all()->first()->toArray();
        $this->assertSame($user_1->id, $involved_first['user_id']);
        $this->assertSame(MorphMap::TICKETS, $involved_first['source_type']);
        $this->assertSame($ticket['id'], $involved_first['source_id']);
        $this->assertSame($project->id, $involved_first['project_id']);
        $this->assertSame($company->id, $involved_first['company_id']);

        /** @var Involved $involved_last */
        $involved_last = Involved::all()->last()->toArray();
        $this->assertSame($user_2->id, $involved_last['user_id']);
        $this->assertSame(MorphMap::TICKETS, $involved_last['source_type']);
        $this->assertSame($ticket['id'], $involved_last['source_id']);
        $this->assertSame($project->id, $involved_last['project_id']);
        $this->assertSame($company->id, $involved_last['company_id']);

        /** @var Ticket $ticket */
        $ticket = Ticket::first();

        $this->assertCount(1, Interaction::all());
        /** @var Interaction $interaction */
        $interaction = Interaction::first();
        $this->assertEquals($this->user->id , $interaction->user_id);
        $this->assertEquals($project->id, $interaction->project_id);
        $this->assertEquals(InteractionEventType::TICKET_INVOLVED_ASSIGNED, $interaction->event_type);
        $this->assertEquals(ActionType::INVOLVED, $interaction->action_type);
        $this->assertEquals($ticket->id, $interaction->source_id);
        $this->assertEquals(SourceType::TICKET, $interaction->source_type);

        $this->assertCount(2, InteractionPing::all());
        /** @var InteractionPing $interaction_ping */
        $interaction_ping = InteractionPing::first();
        $this->assertEquals($user_1->id, $interaction_ping->recipient_id);
        $this->assertNull($interaction_ping->ref);
        $this->assertEquals(NotifiableType::USER, $interaction_ping->notifiable);
        $this->assertNull($interaction_ping->message);
        $this->assertEquals($interaction->id, $interaction_ping->interaction_id);

        /** @var InteractionPing $interaction_ping */
        $interaction_ping = InteractionPing::all()->last();
        $this->assertEquals($user_2->id, $interaction_ping->recipient_id);
        $this->assertNull($interaction_ping->ref);
        $this->assertEquals(NotifiableType::USER, $interaction_ping->notifiable);
        $this->assertNull($interaction_ping->message);
        $this->assertEquals($interaction->id, $interaction_ping->interaction_id);

        $this->assertCount(2, DatabaseNotification::all());

        /** @var DatabaseNotification $notification */
        $notification = $user_2->notifications()->first();
        $this->assertInstanceOf(DatabaseNotification::class, $notification);
        $this->assertSame([
            'project_id' => $project->id,
            'author_id' => $user->id,
            'action_type' => ActionType::INVOLVED,
            'event_type' => InteractionEventType::TICKET_INVOLVED_ASSIGNED,
            'source_type' => SourceType::TICKET,
            'source_id' => $ticket->id,
            'ref' => null,
            'message' => null,
        ], $notification->data);
        $this->assertSame($company->getCompanyId(), $notification->company_id);

        /** @var DatabaseNotification $notification */
        $notification = $user_1->notifications()->first();
        $this->assertInstanceOf(DatabaseNotification::class, $notification);
        $this->assertSame([
            'project_id' => $project->id,
            'author_id' => $user->id,
            'action_type' => ActionType::INVOLVED,
            'event_type' => InteractionEventType::TICKET_INVOLVED_ASSIGNED,
            'source_type' => SourceType::TICKET,
            'source_id' => $ticket->id,
            'ref' => null,
            'message' => null,
        ], $notification->data);
        $this->assertSame($company->getCompanyId(), $notification->company_id);

        $this->assertCount(1, Interaction::all());
        /** @var Interaction $interaction */
        $interaction = Interaction::first();
        $this->assertEquals($this->user->id , $interaction->user_id);
        $this->assertEquals($project->id, $interaction->project_id);
        $this->assertEquals(InteractionEventType::TICKET_INVOLVED_ASSIGNED, $interaction->event_type);
        $this->assertEquals(ActionType::INVOLVED, $interaction->action_type);
        $this->assertEquals($ticket->id, $interaction->source_id);
        $this->assertEquals(SourceType::TICKET, $interaction->source_type);

        $this->assertCount(2, InteractionPing::all());
        /** @var InteractionPing $interaction_ping */
        $interaction_ping = InteractionPing::first();
        $this->assertEquals($user_1->id, $interaction_ping->recipient_id);
        $this->assertNull($interaction_ping->ref);
        $this->assertEquals(NotifiableType::USER, $interaction_ping->notifiable);
        $this->assertNull($interaction_ping->message);
        $this->assertEquals($interaction->id, $interaction_ping->interaction_id);

        /** @var InteractionPing $interaction_ping */
        $interaction_ping = InteractionPing::all()->last();
        $this->assertEquals($user_2->id, $interaction_ping->recipient_id);
        $this->assertNull($interaction_ping->ref);
        $this->assertEquals(NotifiableType::USER, $interaction_ping->notifiable);
        $this->assertNull($interaction_ping->message);
        $this->assertEquals($interaction->id, $interaction_ping->interaction_id);

        $this->assertCount(2, DatabaseNotification::all());

        /** @var DatabaseNotification $notification */
        $notification = $user_2->notifications()->first();
        $this->assertInstanceOf(DatabaseNotification::class, $notification);
        $this->assertSame([
            'project_id' => $project->id,
            'author_id' => $this->user->id,
            'action_type' => ActionType::INVOLVED,
            'event_type' => InteractionEventType::TICKET_INVOLVED_ASSIGNED,
            'source_type' => SourceType::TICKET,
            'source_id' => $ticket->id,
            'ref' => null,
            'message' => null,
        ], $notification->data);
        $this->assertSame($company->getCompanyId(), $notification->company_id);

        /** @var DatabaseNotification $notification */
        $notification = $user_1->notifications()->first();
        $this->assertInstanceOf(DatabaseNotification::class, $notification);
        $this->assertSame([
            'project_id' => $project->id,
            'author_id' => $this->user->id,
            'action_type' => ActionType::INVOLVED,
            'event_type' => InteractionEventType::TICKET_INVOLVED_ASSIGNED,
            'source_type' => SourceType::TICKET,
            'source_id' => $ticket->id,
            'ref' => null,
            'message' => null,
        ], $notification->data);
        $this->assertSame($company->getCompanyId(), $notification->company_id);
    }
}
