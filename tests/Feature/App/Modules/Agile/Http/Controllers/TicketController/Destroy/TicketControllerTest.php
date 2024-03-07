<?php

namespace Tests\Feature\App\Modules\Agile\Http\Controllers\TicketController\Destroy;

use App\Helpers\ErrorCode;
use App\Models\Db\History;
use App\Models\Db\HistoryField;
use App\Models\Db\Interaction;
use App\Models\Db\InteractionPing;
use App\Models\Db\Involved;
use App\Models\Db\Ticket;
use App\Models\Db\TicketRealization;
use App\Models\Db\User;
use App\Models\Other\Interaction\ActionType;
use App\Models\Other\Interaction\InteractionEventType;
use App\Models\Other\Interaction\NotifiableType;
use App\Models\Other\Interaction\SourceType;
use App\Models\Other\RoleType;
use App\Modules\Agile\Events\DeleteTicketEvent;
use App\Modules\Agile\Services\HistoryService;
use App\Modules\Notification\Models\DatabaseNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Tests\BrowserKitTestCase;
use Tests\Feature\App\Modules\Agile\Http\Controllers\TicketController\TestTrait;
use Tests\Helpers\ProjectHelper;
use Tests\Helpers\ResponseHelper;

class TicketControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions, ProjectHelper, ResponseHelper, TestTrait, TicketControllerTrait;

    /**
     * @scenario Ticket Destroying
     * @suit Ticket Destroying
     * @case Return error when user hasn't permission
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::destroy
     * @test
     */
    public function destroy_error_has_not_permission()
    {
        $this->initEnv();

        $project_2 = $this->createProject('project2');
        $ticket = $this->createTicket($project_2->id, 1);
        $url = $this->prepareUrl($this->project->id, $ticket->id, $this->company->id);

        $this->delete($url);
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /**
     * @scenario Ticket Destroying
     * @suit Ticket Destroying
     * @case Return error when user isn't reporter of ticket
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::destroy
     * @test
     */
    public function destroy_return_401_when_destroy_as_not_reporter()
    {
        $this->initEnv();

        $this->project->permission->ticket_destroy = [
            'relations' => [
                ['name' => 'reporter', 'value' => true],
            ],
        ];
        $this->project->permission->save();
        $fake_user = factory(User::class)->create();
        $ticket = $this->createTicket($this->project->id, 1);
        $ticket->reporter_id = $fake_user->id;
        $ticket->save();
        $url = $this->prepareUrl($this->project->id, $ticket->id, $this->company->id);

        $this->delete($url);

        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /**
     * @scenario Ticket Destroying
     * @suit Ticket Destroying
     * @case Return success when user is reporter of ticket
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::destroy
     * @test
     */
    public function destroy_return_success_when_destroy_as_reporter()
    {
        $this->initEnv();

        $this->project->permission->ticket_destroy = [
            'relations' => [
                ['name' => 'reporter', 'value' => true],
            ],
        ];
        $this->project->permission->save();
        $ticket = $this->createTicket($this->project->id, 1);
        $ticket->reporter_id = $this->user->id;
        $ticket->save();
        $url = $this->prepareUrl($this->project->id, $ticket->id, $this->company->id);

        $this->delete($url)->seeStatusCode(204);
    }

    /**
     * @scenario Ticket Destroying
     * @suit Ticket Destroying
     * @case Return error when user isn't assigned to ticket
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::destroy
     * @test
     */
    public function destroy_return_401_when_destroy_as_not_assigned_user()
    {
        $this->initEnv();

        $this->project->permission->ticket_destroy = [
            'relations' => [
                ['name' => 'assigned', 'value' => true],
            ],
        ];
        $this->project->permission->save();
        $fake_user = factory(User::class)->create();
        $ticket = $this->createTicket($this->project->id, 1);
        $ticket->assigned_id = $fake_user->id;
        $ticket->save();
        $url = $this->prepareUrl($this->project->id, $ticket->id, $this->company->id);

        $this->delete($url);

        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /**
     * @scenario Ticket Destroying
     * @suit Ticket Destroying
     * @case Return success when user is assigned to ticket
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::destroy
     * @test
     */
    public function destroy_return_success_when_destroy_as_assigned_user()
    {
        $this->initEnv();

        $this->project->permission->ticket_destroy = [
            'relations' => [
                ['name' => 'assigned', 'value' => true],
            ],
        ];
        $this->project->permission->save();
        $ticket = $this->createTicket($this->project->id, 1);
        $ticket->assigned_id = $this->user->id;
        $ticket->save();
        $url = $this->prepareUrl($this->project->id, $ticket->id, $this->company->id);

        $this->delete($url)->seeStatusCode(204);
    }

    /**
     * @scenario Ticket Destroying
     * @suit Ticket Destroying
     * @case Return error when user hasn't required role
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::destroy
     * @test
     */
    public function destroy_return_401_when_destroy_without_required_role()
    {
        $this->initEnv(RoleType::ADMIN);

        $this->project->permission->ticket_destroy = [
            'roles' => [
                ['name' => 'owner', 'value' => true],
            ],
        ];
        $this->project->permission->save();
        $ticket = $this->createTicket($this->project->id, 1);
        $url = $this->prepareUrl($this->project->id, $ticket->id, $this->company->id);

        $this->delete($url);

        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /**
     * @scenario Ticket Destroying
     * @suit Ticket Destroying
     * @case Return success when user has required role
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::destroy
     * @test
     */
    public function destroy_return_success_when_destroy_with_required_role()
    {
        $this->initEnv(RoleType::OWNER);

        $this->project->permission->ticket_destroy = [
            'roles' => [
                ['name' => 'owner', 'value' => true],
            ],
        ];
        $this->project->permission->save();
        $ticket = $this->createTicket($this->project->id, 1);
        $url = $this->prepareUrl($this->project->id, $ticket->id, $this->company->id);

        $this->delete($url)->seeStatusCode(204);
    }

    /**
     * @scenario Ticket Destroying
     * @suit Ticket Destroying
     * @case Return error when ticket not exist
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::destroy
     * @test
     */
    public function destroy_error_ticket_not_exist()
    {
        $this->initEnv();
        $url = $this->prepareUrl($this->project->id, 0, $this->company->id);

        $this->delete($url);
        $this->verifyErrorResponse(404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /**
     * @scenario Ticket Destroying
     * @suit Ticket Destroying
     * @case Destroy successfully
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::destroy
     * @test
     */
    public function destroy_success_response()
    {
        Event::fake();
        $this->initEnv();

        $ticket = $this->createTicket($this->project->id, 1);
        $this->project->permission()->create([
            'ticket_destroy' => [
                'roles' => [
                    ['name' => 'admin', 'value' => true],
                ],
            ],
        ]);
        $url = $this->prepareUrl($this->project->id, $ticket->id, $this->company->id);

        $this->delete($url, [])->seeStatusCode(204);

        Event::assertDispatched(DeleteTicketEvent::class, function ($e) use ($ticket) {
            if ($e->ticket->id == $ticket->id && $e->project->id == $this->project->id) {
                return true;
            }
        });
    }

    /**
     * @scenario Ticket Destroying
     * @suit Ticket Destroying
     * @case Verify data stored in database after successfully destroy
     *
     * @covers \App\Modules\Agile\Http\Controllers\TicketController::destroy
     * @test
     */
    public function destroy_success_db()
    {
        $this->initEnv();

        $ticket = $this->createTicket($this->project->id, 1);
        $ticket_realisation = factory(TicketRealization::class)->create([
            'ticket_id' => $ticket->id,
            'end_at' => null,
        ]);
        $before_tickets_count = Ticket::count();
        $before_history_count = History::count();
        $url = $this->prepareUrl($this->project->id, $ticket->id, $this->company->id);

        $this->delete($url)->seeStatusCode(204);

        $this->assertNotNull($ticket->fresh()->deleted_at);
        $this->assertEquals($before_tickets_count - 1, Ticket::count());
        $this->assertEquals($before_history_count + 1, History::count());
        // history
        $history = History::latest('id')->first();
        $this->assertSame($this->user->id, $history->user_id);
        $this->assertSame($ticket->id, $history->resource_id);
        $this->assertSame($ticket->id, $history->object_id);
        $this->assertSame(
            HistoryField::getId(HistoryService::TICKET, 'deleted_at'),
            $history->field_id
        );
        $this->assertSame($ticket->text, $history->value_before);
        $this->assertNull($history->label_before);
        $this->assertSame($this->now->toDateTimeString(), $history->value_after);
        $this->assertNull($history->label_after);
        $this->assertSame(
            $this->now->toDateTimeString(),
            $history->created_at->toDateTimeString()
        );
        $this->assertNotNull($ticket_realisation->fresh()->end_at);
    }

    /**
     * @feature Ticket
     * @scenario Delete ticket
     * @case Ticket with interaction and two ping
     * @expectation Interaction success deleted from db
     * @test
     */
    public function delete_ticketWithInteractionAndTwoPing()
    {
        //GIVEN
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = $this->createNewProject($company->id);

        $ticket = $this->createNewTicket([
            'project_id' => $project->id
        ]);

        $interaction = $this->createInteraction();

        $this->createInteractionPing($interaction->id);
        $this->createInteractionPing($interaction->id);

        $ticket->interactions()->save($interaction);

        $url = $this->prepareUrl($project->id, $ticket->id, $company->id);

        //WHEN
        $this->delete($url);

        //THEN
        $this->assertResponseStatus(204);
        $this->assertCount(0, Interaction::all());
        $this->assertCount(0, InteractionPing::all());
    }

    /**
     * @feature Involved
     * @scenario Delete ticket with involved list
     * @case Involved list contains two position
     * @expectation Empty involved list
     * @test
     */
    public function destroy_involvedListContainsTwoPosition()
    {
        //GIVEN
        $user = $this->createAndBeUser();

        $company = $this->createCompany();
        $project = $this->createNewProject($company->getCompanyId());
        $this->setProjectRole($project, RoleType::ADMIN, $user);

        $ticket = $this->createNewTicket([
            'project_id' => $project->id,
        ]);

        $user_involved_1 = $this->createNewUser();
        $user_involved_2 = $this->createNewUser();

        $involved_1 = $this->createInvolved([
            'user_id' => $user_involved_1->id,
            'company_id' => $company->getCompanyId(),
            'project_id' => $project->id,
        ]);

        $involved_2 = $this->createInvolved([
            'user_id' => $user_involved_2->id,
            'company_id' => $company->getCompanyId(),
            'project_id' => $project->id,
        ]);

        $ticket->involved()->save($involved_1);
        $ticket->involved()->save($involved_2);

        $url = $this->prepareUrl($project->id, $ticket->id, $company->getCompanyId());

        //WHEN
        $this->delete($url);

        //THEN
        $this->seeStatusCode(204);
        $this->assertCount(0, Involved::all());

        $this->assertCount(1, Interaction::all());
        /** @var Interaction $interaction */
        $interaction = Interaction::first();
        $this->assertEquals($user->id, $interaction->user_id);
        $this->assertEquals($project->id, $interaction->project_id);
        $this->assertEquals(InteractionEventType::TICKET_INVOLVED_DELETED, $interaction->event_type);
        $this->assertEquals(ActionType::INVOLVED, $interaction->action_type);
        $this->assertEquals($ticket->id, $interaction->source_id);
        $this->assertEquals(SourceType::TICKET, $interaction->source_type);

        $this->assertCount(2, InteractionPing::all());
        /** @var InteractionPing $interaction_ping */
        $interaction_ping = InteractionPing::first();
        $this->assertEquals($user_involved_1->id, $interaction_ping->recipient_id);
        $this->assertNull($interaction_ping->ref);
        $this->assertEquals(NotifiableType::USER, $interaction_ping->notifiable);
        $this->assertNull($interaction_ping->message);
        $this->assertEquals($interaction->id, $interaction_ping->interaction_id);

        /** @var InteractionPing $interaction_ping */
        $interaction_ping = InteractionPing::all()->last();
        $this->assertEquals($user_involved_2->id, $interaction_ping->recipient_id);
        $this->assertNull($interaction_ping->ref);
        $this->assertEquals(NotifiableType::USER, $interaction_ping->notifiable);
        $this->assertNull($interaction_ping->message);
        $this->assertEquals($interaction->id, $interaction_ping->interaction_id);

        $this->assertCount(2, DatabaseNotification::all());

        /** @var DatabaseNotification $notification */
        $notification = $user_involved_2->notifications()->first();
        $this->assertInstanceOf(DatabaseNotification::class, $notification);
        $this->assertSame([
            'project_id' => $project->id,
            'author_id' => $user->id,
            'action_type' => ActionType::INVOLVED,
            'event_type' => InteractionEventType::TICKET_INVOLVED_DELETED,
            'source_type' => SourceType::TICKET,
            'source_id' => $ticket->id,
            'ref' => null,
            'message' => null,
        ], $notification->data);
        $this->assertSame($company->getCompanyId(), $notification->company_id);

        /** @var DatabaseNotification $notification */
        $notification = $user_involved_1->notifications()->first();
        $this->assertInstanceOf(DatabaseNotification::class, $notification);
        $this->assertSame([
            'project_id' => $project->id,
            'author_id' => $user->id,
            'action_type' => ActionType::INVOLVED,
            'event_type' => InteractionEventType::TICKET_INVOLVED_DELETED,
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
        $this->assertEquals(InteractionEventType::TICKET_INVOLVED_DELETED, $interaction->event_type);
        $this->assertEquals(ActionType::INVOLVED, $interaction->action_type);
        $this->assertEquals($ticket->id, $interaction->source_id);
        $this->assertEquals(SourceType::TICKET, $interaction->source_type);

        $this->assertCount(2, InteractionPing::all());
        /** @var InteractionPing $interaction_ping */
        $interaction_ping = InteractionPing::first();
        $this->assertEquals($user_involved_1->id, $interaction_ping->recipient_id);
        $this->assertNull($interaction_ping->ref);
        $this->assertEquals(NotifiableType::USER, $interaction_ping->notifiable);
        $this->assertNull($interaction_ping->message);
        $this->assertEquals($interaction->id, $interaction_ping->interaction_id);

        /** @var InteractionPing $interaction_ping */
        $interaction_ping = InteractionPing::all()->last();
        $this->assertEquals($user_involved_2->id, $interaction_ping->recipient_id);
        $this->assertNull($interaction_ping->ref);
        $this->assertEquals(NotifiableType::USER, $interaction_ping->notifiable);
        $this->assertNull($interaction_ping->message);
        $this->assertEquals($interaction->id, $interaction_ping->interaction_id);

        $this->assertCount(2, DatabaseNotification::all());

        /** @var DatabaseNotification $notification */
        $notification = $user_involved_2->notifications()->first();
        $this->assertInstanceOf(DatabaseNotification::class, $notification);
        $this->assertSame([
            'project_id' => $project->id,
            'author_id' => $this->user->id,
            'action_type' => ActionType::INVOLVED,
            'event_type' => InteractionEventType::TICKET_INVOLVED_DELETED,
            'source_type' => SourceType::TICKET,
            'source_id' => $ticket->id,
            'ref' => null,
            'message' => null,
        ], $notification->data);
        $this->assertSame($company->getCompanyId(), $notification->company_id);

        /** @var DatabaseNotification $notification */
        $notification = $user_involved_1->notifications()->first();
        $this->assertInstanceOf(DatabaseNotification::class, $notification);
        $this->assertSame([
            'project_id' => $project->id,
            'author_id' => $this->user->id,
            'action_type' => ActionType::INVOLVED,
            'event_type' => InteractionEventType::TICKET_INVOLVED_DELETED,
            'source_type' => SourceType::TICKET,
            'source_id' => $ticket->id,
            'ref' => null,
            'message' => null,
        ], $notification->data);
        $this->assertSame($company->getCompanyId(), $notification->company_id);
    }

    /**
     * @feature Ticket
     * @scenario Delete ticket
     * @case Ticket with interaction with ping and another interaction with ping not attached to interaction
     * @expectation Interaction with ping exist in db
     * @test
     */
    public function delete_ticketWithInteractionWithPingAndAnotherInteractionWithPingNotAttachedToInteraction()
    {
        //GIVEN
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = $this->createNewProject($company->id);
        $ticket = $this->createNewTicket([
            'project_id' => $project->id,
        ]);

        $interaction_1 = $this->createInteraction();
        $interaction_2 = $this->createInteraction();

        $interaction_ping_1 = $this->createInteractionPing($interaction_1->id);
        $interaction_ping_2 = $this->createInteractionPing($interaction_2->id);

        $ticket->interactions()->save($interaction_1);

        $url = $this->prepareUrl($project->id, $ticket->id, $company->id);

        //WHEN
        $this->delete($url);

        //THEN
        $this->assertResponseStatus(204);
        $this->assertCount(1, Interaction::all());
        $this->assertEquals($interaction_2->id, Interaction::first()->id);

        $this->assertCount(1, InteractionPing::all());
        $this->assertEquals($interaction_ping_2->id, InteractionPing::first()->id);
    }
}
