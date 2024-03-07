<?php

namespace Tests\Feature\App\Modules\Agile\Http\Controllers\TicketController\Store;

use App\Helpers\ErrorCode;
use App\Models\Db\History;
use App\Models\Db\HistoryField;
use App\Models\Db\Interaction;
use App\Models\Db\InteractionPing;
use App\Models\Db\Involved;
use App\Models\Db\Sprint;
use App\Models\Db\Status;
use App\Models\Db\Story;
use App\Models\Db\Ticket;
use App\Models\Db\TicketType;
use App\Models\Db\User;
use App\Models\Other\Interaction\ActionType;
use App\Models\Other\Interaction\InteractionEventType;
use App\Models\Other\Interaction\NotifiableGroupType;
use App\Models\Other\Interaction\NotifiableType;
use App\Models\Other\Interaction\SourceType;
use App\Models\Other\MorphMap;
use App\Models\Other\RoleType;
use App\Modules\Agile\Events\CreateTicketEvent;
use App\Modules\Agile\Services\HistoryService;
use App\Modules\Notification\Models\DatabaseNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use Tests\BrowserKitTestCase;
use Tests\Feature\App\Modules\Agile\Http\Controllers\TicketController\TestTrait;
use Tests\Helpers\ProjectHelper;
use Tests\Helpers\ResponseHelper;
use Tests\Helpers\VerifyResponse;

class TicketControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions;
    use ProjectHelper;
    use ResponseHelper;
    use TestTrait, VerifyResponse;
    use TicketControllerTrait;

    /**
     * @scenario Ticket Adding
     * @suit Ticket Adding
     * @case Return error when user hasn't required role
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::store
     * @test
     */
    public function store_return_401_when_store_without_required_role()
    {
        $this->initEnv(RoleType::ADMIN);

        $this->project->permission->ticket_create = [
            'roles' => [
                ['name' => 'owner', 'value' => true],
            ],
        ];
        $this->project->permission->save();
        $url = $this->prepareUrl($this->project->id, $this->company->id);

        $this->post($url, []);

        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /**
     * @scenario Ticket Adding
     * @suit Ticket Adding
     * @case Return error when input data is empty
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::store
     * @test
     */
    public function store_it_returns_validation_error_without_data()
    {
        $this->initEnv();
        $url = $this->prepareUrl($this->project->id, $this->company->id);

        $this->post($url, []);

        $this->verifyValidationResponse(['name', 'estimate_time', 'type_id']);
    }

    /**
     * @scenario Ticket Adding
     * @suit Ticket Adding
     * @case Return error when input data is incorrect
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::store
     * @test
     */
    public function store_it_returns_validation_error_wrong_data()
    {
        $this->initEnv();
        $url = $this->prepareUrl($this->project->id, $this->company->id);
        $other_sprint = factory(Sprint::class)->create(['project_id' => 0]);
        $other_user = factory(User::class)->create();
        $other_story = factory(Story::class)->create(['project_id' => 0]);

        $data_send = [
            'parent_ticket_ids' => 'test',
            'sub_ticket_ids' => 'test',
            'name' => 'test',
            'sprint_id' => $other_sprint->id,
            'type_id' => 0,
            'assigned_id' => $other_user->id,
            'reporter_id' => $other_user->id,
            'estimate_time' => 'asd',
            'story_id' => [$other_story->id],
            'scheduled_time_start' => 'asd',
            'scheduled_time_end' => 'asd',
        ];

        $this->post($url, $data_send);

        $this->verifyValidationResponse([
            'parent_ticket_ids',
            'sub_ticket_ids',
            'sprint_id',
            'type_id',
            'assigned_id',
            'reporter_id',
            'estimate_time',
            'story_id.0',
            'scheduled_time_start',
            'scheduled_time_end',
        ]);
    }

    /**
     * @scenario Ticket Adding
     * @suit Ticket Adding
     * @case Return error when input data is incorrect
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::store
     * @test
     */
    public function store_it_returns_validation_error_wrong_time_2()
    {
        $this->initEnv();
        $url = $this->prepareUrl($this->project->id, $this->company->id);

        $data_send = [
            'scheduled_time_start' => '2017-11-20 10:10:10',
            'scheduled_time_end' => '2017-10-20 10:10:10',
        ];

        $this->post($url, $data_send);

        $this->verifyValidationResponse([
            'scheduled_time_start',
            'scheduled_time_end',
        ]);
    }

    /**
     * @scenario Ticket Adding
     * @suit Ticket Adding
     * @case Success with simple data
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::store
     * @test
     */
    public function store_success_response_simple_data()
    {
        $this->mockInteractionNotificationManager();
        $this->initEnv();
        $url = $this->prepareUrl($this->project->id, $this->company->id);

        $data_send = [
            'name' => ' test ',
            'sprint_id' => 0,
            'type_id' => TicketType::first()->id,
            'assigned_id' => null,
            'reporter_id' => null,
            'description' => '',
            'estimate_time' => 0,
            'scheduled_time_start' => null,
            'scheduled_time_end' => null,
            'story_id' => null,
        ];

        $this->post($url, $data_send)
            ->seeStatusCode(201);

        $response_ticket = $this->decodeResponseJson()['data'];

        $this->assertSame($this->project->id, $response_ticket['project_id']);
        $this->assertSame(0, $response_ticket['sprint_id']);
        $this->assertSame($this->status->id, $response_ticket['status_id']);
        $this->assertSame('test', $response_ticket['name']);
        $this->assertSame('PROJ-2', $response_ticket['title']);
        $this->assertSame(TicketType::first()->id, $response_ticket['type_id']);
        $this->assertSame(null, $response_ticket['assigned_id']);
        $this->assertSame(null, $response_ticket['reporter_id']);
        $this->assertSame('', $response_ticket['description']);
        $this->assertSame(0, $response_ticket['estimate_time']);
        $this->assertSame(null, $response_ticket['scheduled_time_start']);
        $this->assertSame(null, $response_ticket['scheduled_time_end']);
        $this->assertSame(1, $response_ticket['priority']);
        $this->assertSame(0, $response_ticket['hidden']);
        $this->assertSame($this->now->toDateTimeString(), $response_ticket['created_at']);
        $this->assertSame($this->now->toDateTimeString(), $response_ticket['updated_at']);
    }

    /**
     * @scenario Ticket Adding
     * @suit Ticket Adding
     * @case Check data in database
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::store
     * @test
     */
    public function store_success_db_simple_data()
    {
        $this->mockInteractionNotificationManager();
        $this->initEnv();
        $url = $this->prepareUrl($this->project->id, $this->company->id);

        $parent_tickets = factory(Ticket::class, 2)->create();
        $sub_tickets = factory(Ticket::class, 2)->create();

        $data_send = [
            'parent_ticket_ids' => [$parent_tickets[0]->id, $parent_tickets[1]->id],
            'sub_ticket_ids' => [$sub_tickets[0]->id, $sub_tickets[1]->id],
            'name' => ' test ',
            'sprint_id' => 0,
            'type_id' => TicketType::first()->id,
            'assigned_id' => null,
            'reporter_id' => null,
            'description' => '',
            'estimate_time' => 0,
            'scheduled_time_start' => null,
            'scheduled_time_end' => null,
            'story_id' => null,
        ];

        $before_tickets = Ticket::count();
        $before_history = History::count();

        $response = $this->post($url, $data_send)
            ->seeStatusCode(201);

        $this->assertEquals($before_tickets + 1, Ticket::count());
        $ticket = Ticket::latest('id')->first();

        $this->assertSame($this->project->id, $ticket->project_id);
        $this->assertSame(0, $ticket->sprint_id);
        $this->assertSame($this->status->id, $ticket->status_id);
        $this->assertSame('test', $ticket->name);
        $this->assertSame('PROJ-2', $ticket->title);
        $this->assertSame(TicketType::first()->id, $ticket->type_id);
        $this->assertSame(null, $ticket->assigned_id);
        $this->assertSame(null, $ticket->reporter_id);
        $this->assertSame('', $ticket->description);
        $this->assertSame(0, $ticket->estimate_time);
        $this->assertSame(null, $ticket->scheduled_time_start);
        $this->assertSame(null, $ticket->scheduled_time_end);
        $this->assertSame(1, $ticket->priority);
        $this->assertSame(0, $ticket->hidden);
        $this->assertSame(
            $this->now->toDateTimeString(),
            $ticket->created_at->toDateTimeString()
        );
        $this->assertSame(
            $this->now->toDateTimeString(),
            $ticket->updated_at->toDateTimeString()
        );
        $this->assertSame(2, $ticket->project->created_tickets);

        // check parent tickets
        $this->assertCount(2, $ticket->parentTickets);
        $this->assertStringContainsString($parent_tickets[0]->id,
            $ticket->parentTickets->pluck('id'));
        $this->assertStringContainsString($parent_tickets[1]->id,
            $ticket->parentTickets->pluck('id'));

        // check sub-tickets
        $this->assertCount(2, $ticket->subTickets);
        $this->assertStringContainsString($sub_tickets[0]->id, $ticket->subTickets->pluck('id'));
        $this->assertStringContainsString($sub_tickets[1]->id, $ticket->subTickets->pluck('id'));

        //history
        $this->assertEquals($before_history + 1, History::count());
        $history = History::latest('id')->first();
        $this->assertSame($this->user->id, $history->user_id);
        $this->assertSame($ticket->id, $history->resource_id);
        $this->assertSame($ticket->id, $history->object_id);
        $this->assertSame(
            HistoryField::getId(HistoryService::TICKET, 'created_at'),
            $history->field_id
        );
        $this->assertSame($ticket->text, $history->value_before);
        $this->assertSame(null, $history->label_before);
        $this->assertSame($this->now->toDateTimeString(), $history->value_after);
        $this->assertSame(null, $history->label_after);
        $this->assertSame(
            $this->now->toDateTimeString(),
            $history->created_at->toDateTimeString()
        );
    }

    /**
     * @scenario Ticket Adding
     * @suit Ticket Adding
     * @case Return error when project doesn't have any statuses
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::store
     * @test
     */
    public function store_it_returns_error_when_project_doesnt_have_any_statuses()
    {
        $this->mockInteractionNotificationManager();
        $this->initEnv();
        $url = $this->prepareUrl($this->project->id, $this->company->id);

        Status::query()->delete();

        $data_send = [
            'name' => ' test ',
            'sprint_id' => 0,
            'type_id' => TicketType::first()->id,
            'assigned_id' => null,
            'reporter_id' => null,
            'description' => '',
            'estimate_time' => 0,
            'story_id' => null,
        ];

        $before_tickets = Ticket::count();
        $before_history = History::count();

        $this->post($url, $data_send);

        $this->verifyErrorResponse(409, ErrorCode::PROJECT_NO_STATUSES);

        $this->assertSame($before_tickets, Ticket::count());
        $this->assertSame($before_history, History::count());
    }

    /**
     * @scenario Ticket Adding
     * @suit Ticket Adding
     * @case Success with all data
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::store
     * @test
     */
    public function store_success_response_all_data()
    {
        $this->mockInteractionNotificationManager();
        Event::fake();

        $this->initEnv();
        // manually creating permissions because event is fake
        $this->project->permission()->create([
            'ticket_create' => [
                'roles' => [
                    ['name' => 'admin', 'value' => true],
                ],
            ],
        ]);
        $url = $this->prepareUrl($this->project->id, $this->company->id);

        $data_send = [
            'name' => ' test ',
            'sprint_id' => $this->sprint->id,
            'type_id' => TicketType::first()->id,
            'assigned_id' => $this->user->id,
            'reporter_id' => $this->user->id,
            'description' => ' sdf dsfds sf ',
            'estimate_time' => 123,
            'scheduled_time_start' => '2017-10-10 10:10:10',
            'scheduled_time_end' => '2017-10-11 10:10:10',
            'story_id' => [$this->story->id],
        ];

        $this->post($url, $data_send)
            ->seeStatusCode(201);

        $response_ticket = $this->decodeResponseJson()['data'];

        Event::assertDispatched(CreateTicketEvent::class, function ($e) use ($response_ticket) {
            if ($e->ticket->id == $response_ticket['id'] &&
                $e->project->id == $response_ticket['project_id']) {
                return true;
            }

            return false;
        });

        $this->assertSame($this->project->id, $response_ticket['project_id']);
        $this->assertSame($this->sprint->id, $response_ticket['sprint_id']);
        $this->assertSame($this->status->id, $response_ticket['status_id']);
        $this->assertSame('test', $response_ticket['name']);
        $this->assertSame('PROJ-2', $response_ticket['title']);
        $this->assertSame(TicketType::first()->id, $response_ticket['type_id']);
        $this->assertSame($this->user->id, $response_ticket['assigned_id']);
        $this->assertSame($this->user->id, $response_ticket['reporter_id']);
        $this->assertSame('sdf dsfds sf', $response_ticket['description']);
        $this->assertSame(123, $response_ticket['estimate_time']);
        $this->assertSame('2017-10-10 10:10:10', $response_ticket['scheduled_time_start']);
        $this->assertSame('2017-10-11 10:10:10', $response_ticket['scheduled_time_end']);
        $this->assertSame(1, $response_ticket['priority']);
        $this->assertSame(0, $response_ticket['hidden']);
        $this->assertSame($this->now->toDateTimeString(), $response_ticket['created_at']);
        $this->assertSame($this->now->toDateTimeString(), $response_ticket['updated_at']);
    }

    /**
     * @scenario Ticket Adding
     * @suit Ticket Adding
     * @case Check all data in database
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::store
     * @test
     */
    public function store_success_db_all_data()
    {
        $this->mockInteractionNotificationManager();
        $this->initEnv();
        $url = $this->prepareUrl($this->project->id, $this->company->id);

        $data_send = [
            'name' => ' test ',
            'sprint_id' => $this->sprint->id,
            'type_id' => TicketType::first()->id,
            'assigned_id' => $this->user->id,
            'reporter_id' => $this->user->id,
            'description' => ' sdf dsfds sf ',
            'estimate_time' => 123,
            'scheduled_time_start' => '2017-10-10 10:10:10',
            'scheduled_time_end' => '2017-10-11 10:10:10',
            'story_id' => [$this->story->id],
        ];

        $before_tickets = Ticket::count();
        $before_history = History::count();

        $this->post($url, $data_send)
            ->seeStatusCode(201);

        $this->assertEquals($before_tickets + 1, Ticket::count());
        $ticket = Ticket::latest('id')->first();

        $this->assertSame($this->project->id, $ticket->project_id);
        $this->assertSame($this->sprint->id, $ticket->sprint_id);
        $this->assertSame($this->status->id, $ticket->status_id);
        $this->assertSame('test', $ticket->name);
        $this->assertSame('PROJ-2', $ticket->title);
        $this->assertSame(TicketType::first()->id, $ticket->type_id);
        $this->assertSame($this->user->id, $ticket->assigned_id);
        $this->assertSame($this->user->id, $ticket->reporter_id);
        $this->assertSame('sdf dsfds sf', $ticket->description);
        $this->assertSame(123, $ticket->estimate_time);
        $this->assertSame('2017-10-10 10:10:10', $ticket->scheduled_time_start->toDateTimeString());
        $this->assertSame('2017-10-11 10:10:10', $ticket->scheduled_time_end->toDateTimeString());
        $this->assertSame(1, $ticket->priority);
        $this->assertSame(0, $ticket->hidden);
        $this->assertSame(
            $this->now->toDateTimeString(),
            $ticket->created_at->toDateTimeString()
        );
        $this->assertSame(
            $this->now->toDateTimeString(),
            $ticket->updated_at->toDateTimeString()
        );
        $this->assertSame(1, count($ticket->stories));
        $this->assertSame($this->story->id, $ticket->stories[0]->id);
        $this->assertSame(2, $ticket->project->created_tickets);

        //history
        $this->assertEquals($before_history + 1, History::count());
        $history = History::latest('id')->first();
        $this->assertSame($this->user->id, $history->user_id);
        $this->assertSame($ticket->id, $history->resource_id);
        $this->assertSame($ticket->id, $history->object_id);
        $this->assertSame(
            HistoryField::getId(HistoryService::TICKET, 'created_at'),
            $history->field_id
        );
        $this->assertSame($ticket->text, $history->value_before);
        $this->assertSame(null, $history->label_before);
        $this->assertSame($this->now->toDateTimeString(), $history->value_after);
        $this->assertSame(null, $history->label_after);
        $this->assertSame(
            $this->now->toDateTimeString(),
            $history->created_at->toDateTimeString()
        );
    }

    /**
     * @scenario Ticket Adding
     * @suit Ticket Adding
     * @case Success when reporter deleted from company
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::store
     * @test
     */
    public function store_success_reporter_user_deleted_from_company()
    {
        $this->mockInteractionNotificationManager();
        $this->initEnv();
        $other_user_in_company = $this->createUserCompany(RoleType::ADMIN);
        $url = $this->prepareUrl($this->project->id, $this->company->id);

        $data_send = [
            'name' => ' test ',
            'sprint_id' => $this->sprint->id,
            'type_id' => TicketType::first()->id,
            'assigned_id' => $other_user_in_company->id,
            'reporter_id' => $other_user_in_company->id,
            'description' => ' sdf dsfds sf ',
            'estimate_time' => 123,
            'scheduled_time_start' => '2017-10-10 10:10:10',
            'scheduled_time_end' => '2017-10-11 10:10:10',
            'story_id' => [$this->story->id],
        ];

        $this->post($url, $data_send)
            ->seeStatusCode(201);
    }

    /**
     * @scenario Ticket Adding
     * @suit Ticket Adding
     * @case Success when adding only with scheduled_time_start
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::store
     * @test
     */
    public function store_success_only_scheduled_time_start()
    {
        $this->mockInteractionNotificationManager();
        $this->initEnv();
        $url = $this->prepareUrl($this->project->id, $this->company->id);

        $data_send = [
            'name' => ' test ',
            'sprint_id' => 0,
            'type_id' => TicketType::first()->id,
            'assigned_id' => null,
            'reporter_id' => null,
            'description' => '',
            'estimate_time' => 0,
            'scheduled_time_start' => '2017-10-10 10:10:10',
            'scheduled_time_end' => null,
            'story_id' => null,
        ];

        $this->post($url, $data_send)
            ->seeStatusCode(201);

        $response_ticket = $this->decodeResponseJson()['data'];

        $this->assertSame($this->project->id, $response_ticket['project_id']);
        $this->assertSame('test', $response_ticket['name']);
        $this->assertSame('2017-10-10 10:10:10', $response_ticket['scheduled_time_start']);
        $this->assertSame(null, $response_ticket['scheduled_time_end']);
    }

    /**
     * @scenario Ticket Adding
     * @suit Ticket Adding
     * @case Success when adding only with scheduled_time_end
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::store
     * @test
     */
    public function store_success_only_scheduled_time_end()
    {
        $this->mockInteractionNotificationManager();
        $this->initEnv();
        $url = $this->prepareUrl($this->project->id, $this->company->id);

        $data_send = [
            'name' => ' test ',
            'sprint_id' => 0,
            'type_id' => TicketType::first()->id,
            'assigned_id' => null,
            'reporter_id' => null,
            'description' => '',
            'estimate_time' => 0,
            'scheduled_time_start' => null,
            'scheduled_time_end' => '2017-10-10 10:10:10',
            'story_id' => null,
        ];

        $this->post($url, $data_send)
            ->seeStatusCode(201);

        $response_ticket = $this->decodeResponseJson()['data'];

        $this->assertSame($this->project->id, $response_ticket['project_id']);
        $this->assertSame('test', $response_ticket['name']);
        $this->assertSame(null, $response_ticket['scheduled_time_start']);
        $this->assertSame('2017-10-10 10:10:10', $response_ticket['scheduled_time_end']);
    }

    /**
     * @feature Ticket
     * @scenario Add ticket
     * @case Ticket with single interaction
     *
     * @Expectation Success save to db
     *
     * @dataProvider validSingleUserInteractionData
     *
     * @test
     */
    public function store_ticketWithSingleInteraction(array $entry_interaction_data): void
    {
        //GIVEN
        $this->mockInteractionNotificationManager();
        $recipient = $this->createNewUser();

        $this->user = $this->createNewUser();
        auth()->loginUsingId($this->user->id);

        $this->company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = $this->createNewProject($this->company->id);

        $entry_interaction_data[0] = array_merge($entry_interaction_data[0], ['recipient_id' => $recipient->id]);

        $data_send = $this->getDataSendSimple();

        $data_send['interactions'] = [
            'data' => $entry_interaction_data,
        ];

        $url = $this->prepareUrl($project->id, $this->company->id);

        //WHEN
        $response = $this->post($url, $data_send);

        //THEN
        $this->assertResponseStatus(201);

        /** @var Ticket $ticket */
        $ticket = Ticket::first();

        $this->assertCount(1, Interaction::all());
        /** @var Interaction $interaction */
        $interaction = Interaction::first();
        $this->assertEquals($this->user->id , $interaction->user_id);
        $this->assertEquals($project->id, $interaction->project_id);
        $this->assertEquals(InteractionEventType::TICKET_NEW, $interaction->event_type);
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
     * @scenario Add Ticket
     * @case Ticket with two interaction
     *
     * @Expectation Success save to db
     *
     * @dataProvider validTwoUserInteractionData
     *
     * @test
     */
    public function store_ticketWithTwoInteraction(array $entry_interaction_data): void
    {
        //GIVEN
        $this->user = $this->createNewUser();
        auth()->loginUsingId($this->user->id);

        $this->company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = $this->createNewProject($this->company->id);

        $recipient_1 = $this->createNewUser();
        $recipient_2 = $this->createNewUser();

        $entry_interaction_data[0] = array_merge($entry_interaction_data[0], ['recipient_id' => $recipient_1->id]);
        $entry_interaction_data[1] = array_merge($entry_interaction_data[1], ['recipient_id' => $recipient_2->id]);

        $data_send = $this->getDataSendSimple();

        $data_send['interactions'] = [
            'data' => $entry_interaction_data,
        ];

        $url = $this->prepareUrl($project->id, $this->company->id);

        //WHEN
        $this->post($url, $data_send);

        //THEN
        $this->assertResponseStatus(201);

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
        $this->assertSame(2, DatabaseNotification::query()->count());
    }

    /**
     * @feature Ticket
     * @scenario Add ticket
     * @case Ticket with group interaction
     *
     * @Expectation Success save to db
     *
     * @dataProvider validGroupInteractionData
     *
     * @test
     */
    public function store_ticketWithGroupInteraction(array $entry_interaction_data): void
    {
        //GIVEN
        $this->user = $this->createNewUser();
        auth()->loginUsingId($this->user->id);

        $this->company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = $this->createNewProject($this->company->id);

        $entry_interaction_data[0] = array_merge($entry_interaction_data[0], ['recipient_id' => NotifiableGroupType::ALL]);

        $data_send = $this->getDataSendSimple();

        $data_send['interactions'] = [
            'data' => $entry_interaction_data,
        ];

        $url = $this->prepareUrl($project->id, $this->company->id);

        //WHEN
        $this->post($url, $data_send);

        //THEN
        $this->assertResponseStatus(201);

        /** @var Ticket $ticket */
        $ticket = Ticket::first();

        $this->assertCount(1, Interaction::all());
        /** @var Interaction $interaction */
        $interaction = Interaction::first();
        $this->assertEquals($this->user->id , $interaction->user_id);
        $this->assertEquals($project->id, $interaction->project_id);
        $this->assertEquals($this->company->id, $interaction->company_id);
        $this->assertEquals(InteractionEventType::TICKET_NEW, $interaction->event_type);
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
        $this->assertSame(1, DatabaseNotification::query()->count());
    }

    /**
     * @feature Ticket
     * @scenario Ticket Adding
     * @case Ticket with mixed type interaction
     *
     * @Expectation Success save to db
     *
     * @dataProvider validMixedInteractionData
     * @test
     */
    public function store_ticketWithMixedTypeInteraction($entry_interaction_data): void
    {
        //GIVEN
        $this->user = $this->createNewUser();
        auth()->loginUsingId($this->user->id);

        $this->company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = $this->createNewProject($this->company->id);

        $recipient_1 = $this->createNewUser();

        $entry_interaction_data[0] = array_merge($entry_interaction_data[0], ['recipient_id' => $recipient_1->id]);
        $entry_interaction_data[1] = array_merge($entry_interaction_data[1], ['recipient_id' => NotifiableGroupType::ALL]);

        $data_send = $this->getDataSendSimple();

        $data_send['interactions'] = [
                'data' => $entry_interaction_data,
            ];

        $url = $this->prepareUrl($project->id, $this->company->id);

        //WHEN
        /** @var TestResponse $response */
        $response = $this->post($url, $data_send);

        //THEN
        $this->assertResponseStatus(201);

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

        $this->assertSame(2, DatabaseNotification::query()->count());
    }

    /**
     * @feature Involved
     * @scenario Store ticket with involved list
     * @case Involved list contains two position
     * @expectation Involved list contains new positions, Notification contains new positions
     * @test
     */
    public function store_involvedListContainsTwoPosition()
    {
        //GIVEN
        $user = $this->createAndBeUser();

        $company = $this->createCompany();
        $project = $this->createNewProject($company->getCompanyId());
        $this->setProjectRole($project, RoleType::ADMIN, $user);

        $status = $this->createStatus(1, $project->id);
        $project->update(['status_for_calendar_id' => $status->id]);

        $user_1 = $this->createNewUser();
        $user_2 = $this->createNewUser();

        $project->users()->save($user_1);
        $project->users()->save($user_2);

        $data_send = [
            'name' => ' test ',
            'sprint_id' => 0,
            'type_id' => TicketType::first()->id,
            'estimate_time' => 0,
            'involved_ids' => [
                $user_1->id,
                $user_2->id,
            ],
        ];

        $url = $this->prepareUrl($project->id, $company->id);

        //WHEN
        $this->post($url, $data_send);

        //THEN
        $this->seeStatusCode(201);
        $this->assertCount(2, Involved::all());

        $response_ticket = $this->decodeResponseJson()['data'];

        /** @var Involved $involved_first */
        $involved_first = Involved::all()->first()->toArray();
        $this->assertSame($user_1->id, $involved_first['user_id']);
        $this->assertSame(MorphMap::TICKETS, $involved_first['source_type']);
        $this->assertSame($response_ticket['id'], $involved_first['source_id']);
        $this->assertSame($project->id, $involved_first['project_id']);
        $this->assertSame($company->id, $involved_first['company_id']);

        /** @var Involved $involved_last */
        $involved_last = Involved::all()->last()->toArray();
        $this->assertSame($user_2->id, $involved_last['user_id']);
        $this->assertSame(MorphMap::TICKETS, $involved_last['source_type']);
        $this->assertSame($response_ticket['id'], $involved_last['source_id']);
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
    }

    /**
     * @feature Involved
     * @scenario Store ticket with involved list
     * @case Involved list contains user which is not in project
     * @expectation Return validation error
     * @test
     */
    public function store_involvedListContainsUserWhichIsNotInProject()
    {
        //GIVEN
        $user = $this->createAndBeUser();

        $company = $this->createCompany();
        $project = $this->createNewProject($company->getCompanyId());
        $this->setProjectRole($project, RoleType::ADMIN, $user);

        $status = $this->createStatus(1, $project->id);
        $project->update(['status_for_calendar_id' => $status->id]);

        $user_1 = $this->createNewUser();

        $data_send = [
            'name' => ' test ',
            'sprint_id' => 0,
            'type_id' => TicketType::first()->id,
            'estimate_time' => 0,
            'involved_ids' => [
                $user_1->id,
            ],
        ];

        $url = $this->prepareUrl($project->id, $company->id);

        //WHEN
        $this->post($url, $data_send);

        //THEN
        $this->seeStatusCode(422);
        $this->assertStringContainsString('This users are not in project', $this->response->getContent());
    }
}
