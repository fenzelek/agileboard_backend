<?php

namespace Tests\Feature\App\Modules\Agile\Http\Controllers\TicketCommentController\Update;

use App\Helpers\ErrorCode;
use App\Models\Db\History;
use App\Models\Db\HistoryField;
use App\Models\Db\Interaction;
use App\Models\Db\InteractionPing;
use App\Models\Db\Project;
use App\Models\Db\Ticket;
use App\Models\Db\TicketComment;
use App\Models\Other\Interaction\ActionType;
use App\Models\Other\Interaction\InteractionEventType;
use App\Models\Other\Interaction\NotifiableGroupType;
use App\Models\Other\Interaction\NotifiableType;
use App\Models\Other\RoleType;
use App\Modules\Agile\Events\UpdateCommentEvent;
use App\Modules\Agile\Services\HistoryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Tests\BrowserKitTestCase;
use Tests\Helpers\ProjectHelper;

class TicketCommentControllerTest extends BrowserKitTestCase
{
    use DatabaseTransactions;
    use ProjectHelper;
    use TicketCommentControllerTrait;

    /** @test */
    public function update_return_401_when_update_without_required_role()
    {
        //GIVEN
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);
        $ticket = factory(Ticket::class)->create(['project_id' => $project->id]);
        $comment = factory(TicketComment::class)->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
        ]);
        $project->permission->ticket_comment_update = [
            'roles' => [
                'owner',
            ],
        ];
        $project->permission->save();

        //WHEN
        $this->put('/projects/' . $project->id . '/comments/' . $comment->id .
            '?selected_company_id=' . $company->id);

        //THEN
        $this->assertResponseStatus(401);
    }

    /** @test */
    public function update_it_returns_validation_error_without_data()
    {
        //GIVEN
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);
        $ticket = factory(Ticket::class)->create(['project_id' => $project->id]);
        $comment = factory(TicketComment::class)->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
        ]);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        //WHEN
        $this->put('/projects/' . $project->id . '/comments/' . $comment->id .
            '?selected_company_id=' . $company->id);

        //THEN
        $this->verifyValidationResponse(['text']);
    }

    /** @test */
    public function update_error_has_not_permission()
    {
        //GIVEN
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $project_2 = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);
        $ticket = factory(Ticket::class)->create(['project_id' => $project_2->id]);
        $comment = factory(TicketComment::class)->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
        ]);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        //WHEN
        $this->put('/projects/' . $project->id . '/comments/' . $comment->id .
            '?selected_company_id=' . $company->id, ['text' => 'asdasd']);

        //THEN
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function update_error_comment_not_exist()
    {
        //GIVEN
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);

        //WHEN
        $this->put('/projects/' . $project->id . '/comments/0?selected_company_id=' .
            $company->id, ['text' => 'update']);

        //THEN
        $this->verifyErrorResponse(404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /** @test */
    public function update_success_response()
    {
        //GIVEN
        $this->mockInteractionNotificationManager();

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        Event::fake();

        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);
        $ticket = factory(Ticket::class)->create(['project_id' => $project->id]);
        $comment = $this->createComment($ticket->id, $this->user->id);
        // manually creating permissions because event is fake
        $project->permission()->create([
            'ticket_comment_update' => [
                'roles' => [
                    ['name' => 'admin', 'value' => true],
                ],
            ],
        ]);

        //WHEN
        $this->put('/projects/' . $project->id . '/comments/' . $comment->id .
            '?selected_company_id=' . $company->id, ['text' => ' sdsadasd ']);

        //THEN
        $this->assertResponseStatus(200);
        Event::assertDispatched(UpdateCommentEvent::class, function ($e) use ($project, $ticket) {
            if ($e->project->id == $project->id && $e->ticket->id == $ticket->id) {
                return true;
            }
        });

        $response_comment = $this->decodeResponseJson()['data'];

        $this->assertSame('sdsadasd', $response_comment['text']);
        $this->assertSame($ticket->id, $response_comment['ticket_id']);
        $this->assertSame($this->user->id, $response_comment['user_id']);
        $this->assertSame($now->toDateTimeString(), $response_comment['created_at']);
        $this->assertSame($now->toDateTimeString(), $response_comment['updated_at']);
    }

    /** @test */
    public function update_success_db()
    {
        //GIVEN
        $this->mockInteractionNotificationManager();
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $this->setProjectRole($project);
        $ticket = factory(Ticket::class)->create(['project_id' => $project->id]);

        $comment = $this->createComment($ticket->id, $this->user->id);

        $before_comment = TicketComment::count();
        $before_history = History::count();

        //WHEN
        $this->put('/projects/' . $project->id . '/comments/' . $comment->id .
            '?selected_company_id=' . $company->id, ['text' => ' sdsadasd ']);

        //THEN
        //history
        $this->assertResponseStatus(200);
        $this->assertEquals($before_history + 1, History::count());
        $history = History::latest('id')->first();
        $this->assertSame($this->user->id, $history->user_id);
        $this->assertSame($comment->ticket_id, $history->resource_id);
        $this->assertSame($comment->id, $history->object_id);
        $this->assertSame(HistoryField::getId(HistoryService::TICKET_COMMENT, 'text'), $history->field_id);
        $this->assertSame($comment->text, $history->value_before);
        $this->assertSame(null, $history->label_before);
        $this->assertSame('sdsadasd', $history->value_after);
        $this->assertSame(null, $history->label_after);
        $this->assertSame($now->toDateTimeString(), $history->created_at->toDateTimeString());

        $this->assertEquals($before_comment, TicketComment::count());
        $comment = $comment->fresh();

        $this->assertSame('sdsadasd', $comment->text);
        $this->assertSame($ticket->id, $comment->ticket_id);
        $this->assertSame($this->user->id, $comment->user_id);
        $this->assertSame($now->toDateTimeString(), $comment->created_at->toDateTimeString());
        $this->assertSame($now->toDateTimeString(), $comment->updated_at->toDateTimeString());
    }

     /**
     * @feature Ticket
     * @scenario Update Comment ticket
     * @case Comment with single interaction
     *
     * @Expectation Success save to db
     *
     * @dataProvider validSingleUserInteractionData
     *
     * @test
     */
    public function update_commentWithSingleInteraction(array $entry_interaction_data)
    {
        //GIVEN
        $this->mockInteractionNotificationManager();
        $recipient = $this->createNewUser();

        $this->user = $this->createNewUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);

        $project = $this->createProject($company->id);
        $this->setProjectRole($project);
        $ticket = $this->createTicket($project->id);

        $entry_interaction_data[0] = array_merge($entry_interaction_data[0], ['recipient_id' => $recipient->id]);

        $comment = $this->createComment($ticket->id, $this->user->id);

        $data = [
            'text' => 'test comment text',
            'interactions' => [
                'data' => $entry_interaction_data
            ]
        ];

        //WHEN
        $this->put('/projects/' . $project->id . '/comments/' . $comment->id .
            '?selected_company_id=' . $company->id, $data);

        //THEN
        $this->assertResponseStatus(200);

        /** @var TicketComment $comment */
        $comment = TicketComment::first();

        $this->assertCount(1, Interaction::all());
        /** @var Interaction $interaction */
        $interaction = Interaction::first();
        $this->assertEquals($this->user->id , $interaction->user_id);
        $this->assertEquals($project->id, $interaction->project_id);
        $this->assertEquals($company->id, $interaction->company_id);
        $this->assertEquals(InteractionEventType::TICKET_COMMENT_EDIT, $interaction->event_type);
        $this->assertEquals(ActionType::PING, $interaction->action_type);
        $this->assertEquals($comment->id, $interaction->source_id);
        $this->assertEquals('ticket_comments', $interaction->source_type);

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
     * @scenario Update Comment ticket
     * @case Comment with two interaction
     *
     * @Expectation Success save to db
     *
     * @dataProvider validTwoUserInteractionData
     *
     * @test
     */
    public function update_commentWithTwoInteraction(array $entry_interaction_data)
    {
        //GIVEN
        $this->mockInteractionNotificationManager();
        $recipient_1 = $this->createNewUser();
        $recipient_2 = $this->createNewUser();

        $this->user = $this->createNewUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);

        $project = $this->createProject($company->id);
        $this->setProjectRole($project);
        $ticket = $this->createTicket($project->id);

        $comment = $this->createComment($ticket->id, $this->user->id);

        $entry_interaction_data[0] = array_merge($entry_interaction_data[0], ['recipient_id' => $recipient_1->id]);
        $entry_interaction_data[1] = array_merge($entry_interaction_data[1], ['recipient_id' => $recipient_2->id]);

        $data = [
            'text' => 'test comment text',
            'interactions' => [
                'data' => $entry_interaction_data
            ]
        ];

        //WHEN
        $this->put('/projects/' . $project->id . '/comments/' . $comment->id .
            '?selected_company_id=' . $company->id, $data);

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
     * @scenario Update Comment ticket
     * @case Comment with group interaction
     *
     * @Expectation Success save to db
     *
     * @dataProvider validGroupInteractionData
     *
     * @test
     */
    public function update_commentWithGroupInteraction(array $entry_interaction_data)
    {
        //GIVEN
        $this->mockInteractionNotificationManager();
        $recipient_1 = $this->createNewUser();
        $recipient_2 = $this->createNewUser();

        $this->user = $this->createNewUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);

        $project = $this->createProject($company->id);
        $this->setProjectRole($project);
        $ticket = $this->createTicket($project->id);

        $comment = $this->createComment($ticket->id, $this->user->id);

        $entry_interaction_data[0] = array_merge($entry_interaction_data[0], ['recipient_id' => NotifiableGroupType::ALL]);

        $data = [
            'text' => 'text',
            'interactions' => [
                'data' => $entry_interaction_data
            ]
        ];

        //WHEN
        $this->put('/projects/' . $project->id . '/comments/' . $comment->id .
            '?selected_company_id=' . $company->id, $data);

        //THEN
        $this->assertResponseStatus(200);

        /** @var TicketComment $comment */
        $comment = TicketComment::first();

        $this->assertCount(1, Interaction::all());
        /** @var Interaction $interaction */
        $interaction = Interaction::first();
        $this->assertEquals($this->user->id , $interaction->user_id);
        $this->assertEquals($project->id, $interaction->project_id);
        $this->assertEquals(InteractionEventType::TICKET_COMMENT_EDIT, $interaction->event_type);
        $this->assertEquals(ActionType::PING, $interaction->action_type);
        $this->assertEquals($comment->id, $interaction->source_id);
        $this->assertEquals('ticket_comments', $interaction->source_type);

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
     * @scenario Update Comment ticket
     * @case Comment with mixed type interaction
     *
     * @Expectation Success save to db
     *
     * @dataProvider validMixedInteractionData
     *
     * @test
     */
    public function update_commentWithMixedTypeInteraction(array $entry_interaction_data)
    {
        //GIVEN
        $this->mockInteractionNotificationManager();

        $recipient_1 = $this->createNewUser();

        $this->user = $this->createNewUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);

        $project = $this->createProject($company->id);
        $this->setProjectRole($project);
        $ticket = $this->createTicket($project->id);

        $comment = $this->createComment($ticket->id, $this->user->id);

        $entry_interaction_data[0] = array_merge($entry_interaction_data[0], ['recipient_id' => $recipient_1->id]);
        $entry_interaction_data[1] = array_merge($entry_interaction_data[1], ['recipient_id' => NotifiableGroupType::ALL]);

        $data = [
            'text' => 'test comment text',
            'interactions' => [
                'data' => $entry_interaction_data
            ]
        ];

        //WHEN
        $this->put('/projects/' . $project->id . '/comments/' . $comment->id .
            '?selected_company_id=' . $company->id, $data);

        //THEN
        $this->assertResponseStatus(200);
        /** @var TicketComment $comment */
        $comment = TicketComment::first();

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
}
