<?php

namespace Tests\Feature\App\Modules\Agile\Http\Controllers\TicketCommentController\Store;

use App\Models\Db\History;
use App\Models\Db\HistoryField;
use App\Models\Db\Interaction;
use App\Models\Db\InteractionPing;
use App\Models\Db\TicketComment;
use App\Models\Other\Interaction\ActionType;
use App\Models\Other\Interaction\InteractionEventType;
use App\Models\Other\Interaction\NotifiableGroupType;
use App\Models\Other\Interaction\NotifiableType;
use App\Models\Other\RoleType;
use App\Modules\Agile\Services\HistoryService;
use App\Modules\Notification\Models\DatabaseNotification;
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
    public function store_return_401_when_store_without_required_role()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = $this->createProject($company->id);
        $this->setProjectRole($project);
        $project->permission->ticket_comment_create = [
            'roles' => [
                'owner',
            ],
        ];
        $project->permission->save();

        $this->post(
            '/projects/' . $project->id . '/comments?selected_company_id=' . $company->id,
            []
        )->seeStatusCode(401);
    }

    /** @test */
    public function store_it_returns_validation_error_without_data()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = $this->createProject($company->id);
        $this->setProjectRole($project);

        $this->post(
            '/projects/' . $project->id . '/comments?selected_company_id=' . $company->id,
            []
        );

        $this->verifyValidationResponse(['text', 'ticket_id']);
    }

    /** @test */
    public function store_success_response()
    {
        //GIVEN
        $this->mockInteractionNotificationManager();
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        Event::fake();

        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = $this->createProject($company->id);
        // manually creating permissions because event is fake
        $project->permission()->create([
            'ticket_comment_create' => [
                'roles' => [
                    ['name' => 'admin', 'value' => true],
                ],
            ],
        ]);
        $this->setProjectRole($project);
        $ticket = $this->createTicket($project->id);

        $data = [
            'text' => ' sdsadasd ',
            'ticket_id' => $ticket->id,
        ];

        //WHEN
        $this->post('/projects/' . $project->id . '/comments?selected_company_id=' . $company->id, $data);

        //THEN
        $this->assertResponseStatus(201);

        $response_comment = $this->decodeResponseJson()['data'];

        $this->assertSame('sdsadasd', $response_comment['text']);
        $this->assertSame($ticket->id, $response_comment['ticket_id']);
        $this->assertSame($this->user->id, $response_comment['user_id']);
        $this->assertSame($now->toDateTimeString(), $response_comment['created_at']);
        $this->assertSame($now->toDateTimeString(), $response_comment['updated_at']);
    }

    /** @test */
    public function store_success_db()
    {
        //GIVEN
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        auth()->loginUsingId($this->user->id);

        $company = $this->createCompanyWithRole(RoleType::ADMIN);

        $project = $this->createProject($company->id);
        $this->setProjectRole($project);

        $ticket = $this->createTicket($project->id);

        $before_comment = TicketComment::count();
        $before_history = History::count();

        $data = [
            'text' => ' sdsadasd ',
            'ticket_id' => $ticket->id,
        ];

        //WHEN
        $this->post('/projects/' . $project->id . '/comments?selected_company_id=' . $company->id, $data);

        //THEN
        $this->assertResponseStatus(201);

        $this->assertEquals($before_comment + 1, TicketComment::count());
        $comment = TicketComment::latest('id')->first();

        $this->assertSame('sdsadasd', $comment->text);
        $this->assertSame($ticket->id, $comment->ticket_id);
        $this->assertSame($this->user->id, $comment->user_id);
        $this->assertSame($now->toDateTimeString(), $comment->created_at->toDateTimeString());
        $this->assertSame($now->toDateTimeString(), $comment->updated_at->toDateTimeString());

        //history
        $this->assertEquals($before_history + 1, History::count());
        $history = History::latest('id')->first();
        $this->assertSame($this->user->id, $history->user_id);
        $this->assertSame($comment->ticket_id, $history->resource_id);
        $this->assertSame($comment->id, $history->object_id);
        $this->assertSame(HistoryField::getId(HistoryService::TICKET_COMMENT, 'created_at'), $history->field_id);
        $this->assertSame(null, $history->value_before);
        $this->assertSame(null, $history->label_before);
        $this->assertSame($now->toDateTimeString(), $history->value_after);
        $this->assertSame(null, $history->label_after);
        $this->assertSame($now->toDateTimeString(), $history->created_at->toDateTimeString());
    }

    /**
     * @feature Ticket
     * @scenario Add Comment to ticket
     * @case Comment with single interaction
     *
     * @Expectation Success save to db
     *
     * @dataProvider validSingleUserInteractionData
     *
     * @test
     */
    public function store_commentWithSingleInteraction(array $entry_interaction_data)
    {
        //GIVEN
        $recipient = $this->createNewUser();

        $this->user = $this->createNewUser();
        auth()->loginUsingId($this->user->id);

        $company = $this->createCompanyWithRole(RoleType::ADMIN);

        $project = $this->createProject($company->id);
        $this->setProjectRole($project);
        $ticket = $this->createTicket($project->id);

        $entry_interaction_data[0] = array_merge($entry_interaction_data[0], ['recipient_id' => $recipient->id]);

        $data = [
            'text' => 'test comment text',
            'ticket_id' => $ticket->id,
            'interactions' => [
                'data' => $entry_interaction_data,
            ],
        ];

        //WHEN
        $this->post('/projects/' . $project->id . '/comments?selected_company_id=' . $company->id, $data);

        //THEN
        $this->assertResponseStatus(201);

        /** @var TicketComment $comment */
        $comment = TicketComment::first();

        $this->assertCount(1, Interaction::all());
        /** @var Interaction $interaction */
        $interaction = Interaction::first();
        $this->assertEquals($this->user->id, $interaction->user_id);
        $this->assertEquals($project->id, $interaction->project_id);
        $this->assertEquals(InteractionEventType::TICKET_COMMENT_NEW, $interaction->event_type);
        $this->assertEquals(ActionType::PING, $interaction->action_type);
        $this->assertEquals($comment->id, $interaction->source_id);
        $this->assertEquals('ticket_comments', $interaction->source_type);

        $this->assertCount(1, InteractionPing::all());
        /** @var InteractionPing $interaction_ping */
        $interaction_ping = InteractionPing::first();
        $this->assertEquals($recipient->id, $interaction_ping->recipient_id);
        $this->assertEquals('label test', $interaction_ping->ref);
        $this->assertEquals(NotifiableType::USER, $interaction_ping->notifiable);
        $this->assertEquals($entry_interaction_data[0]['message'], $interaction_ping->message);
        $this->assertEquals($interaction->id, $interaction_ping->interaction_id);
    }

    /**
     * @feature Ticket
     * @scenario Add Comment to ticket
     * @case Comment with two interaction
     *
     * @Expectation Success save to db
     *
     * @dataProvider validTwoUserInteractionData
     *
     * @test
     */
    public function store_commentWithTwoInteraction(array $entry_interaction_data)
    {
        //GIVEN
        $recipient_1 = $this->createNewUser();
        $recipient_2 = $this->createNewUser();

        $this->user = $this->createNewUser();
        auth()->loginUsingId($this->user->id);

        $company = $this->createCompanyWithRole(RoleType::ADMIN);

        $project = $this->createProject($company->id);
        $this->setProjectRole($project);
        $ticket = $this->createTicket($project->id);

        $entry_interaction_data[0] = array_merge($entry_interaction_data[0], ['recipient_id' => $recipient_1->id]);
        $entry_interaction_data[1] = array_merge($entry_interaction_data[1], ['recipient_id' => $recipient_2->id]);

        $data = [
            'text' => 'test comment text',
            'ticket_id' => $ticket->id,
            'interactions' => [
                'data' => $entry_interaction_data,
            ],
        ];

        //WHEN
        $this->post('/projects/' . $project->id . '/comments?selected_company_id=' . $company->id, $data);

        //THEN
        $this->assertResponseStatus(201);

        /** @var TicketComment $comment */
        $comment = TicketComment::first();

        $this->assertCount(1, Interaction::all());
        /** @var Interaction $interaction */
        $interaction = Interaction::first();

        $this->assertCount(2, InteractionPing::all());
        /** @var InteractionPing $interaction_ping_1 */
        $interaction_ping_1 = InteractionPing::first();
        $this->assertEquals($recipient_1->id, $interaction_ping_1->recipient_id);
        $this->assertEquals('label test 1', $interaction_ping_1->ref);
        $this->assertEquals(NotifiableType::USER, $interaction_ping_1->notifiable);
        $this->assertEquals('message test 1', $interaction_ping_1->message);
        $this->assertEquals($interaction->id, $interaction_ping_1->interaction_id);

        /** @var InteractionPing $interaction_ping_2 */
        $interaction_ping_2 = InteractionPing::all()->last();
        $this->assertEquals($recipient_2->id, $interaction_ping_2->recipient_id);
        $this->assertEquals('label test 2', $interaction_ping_2->ref);
        $this->assertEquals(NotifiableType::USER, $interaction_ping_2->notifiable);
        $this->assertEquals('message test 2', $interaction_ping_2->message);
        $this->assertEquals($interaction->id, $interaction_ping_2->interaction_id);

        $this->assertSame(2, DatabaseNotification::query()->count());
    }

    /**
     * @feature Ticket
     * @scenario Add Comment to ticket
     * @case Comment with group interaction
     *
     * @Expectation Success save to db
     *
     * @dataProvider validGroupInteractionData
     *
     * @test
     */
    public function store_commentWithGroupInteraction(array $entry_interaction_data)
    {
        //GIVEN
        $recipient_1 = $this->createNewUser();
        $recipient_2 = $this->createNewUser();

        $this->user = $this->createNewUser();
        auth()->loginUsingId($this->user->id);

        $company = $this->createCompanyWithRole(RoleType::ADMIN);

        $project = $this->createProject($company->id);
        $this->setProjectRole($project);

        $ticket = $this->createTicket($project->id);

        $entry_interaction_data[0] = array_merge($entry_interaction_data[0], ['recipient_id' => NotifiableGroupType::ALL]);

        $data = [
            'text' => 'text',
            'ticket_id' => $ticket->id,
            'interactions' => [
                'data' => $entry_interaction_data,
            ],
        ];

        //WHEN
        $this->post('/projects/' . $project->id . '/comments?selected_company_id=' . $company->id, $data);

        //THEN
        $this->assertResponseStatus(201);

        /** @var TicketComment $comment */
        $comment = TicketComment::first();

        $this->assertCount(1, Interaction::all());
        /** @var Interaction $interaction */
        $interaction = Interaction::first();
        $this->assertEquals($this->user->id, $interaction->user_id);
        $this->assertEquals($project->id, $interaction->project_id);
        $this->assertEquals($company->id, $interaction->company_id);
        $this->assertEquals(InteractionEventType::TICKET_COMMENT_NEW, $interaction->event_type);
        $this->assertEquals(ActionType::PING, $interaction->action_type);
        $this->assertEquals($comment->id, $interaction->source_id);
        $this->assertEquals('ticket_comments', $interaction->source_type);

        $this->assertCount(1, InteractionPing::all());
        /** @var InteractionPing $interaction_ping */
        $interaction_ping = InteractionPing::first();
        $this->assertEquals(NotifiableGroupType::ALL, $interaction_ping->recipient_id);
        $this->assertEquals('label test', $interaction_ping->ref);
        $this->assertEquals(NotifiableType::GROUP, $interaction_ping->notifiable);
        $this->assertEquals('message test', $interaction_ping->message);
        $this->assertEquals($interaction->id, $interaction_ping->interaction_id);
        $this->assertSame(1, DatabaseNotification::query()->count());
    }

    /**
     * @feature Ticket
     * @scenario Add Comment to ticket
     * @case Comment with mixed type interaction
     *
     * @Expectation Success save to db
     *
     * @dataProvider validMixedInteractionData
     *
     * @test
     */
    public function store_commentWithMixedTypeInteraction(array $entry_interaction_data)
    {
        //GIVEN
        $recipient_1 = $this->createNewUser();

        $this->user = $this->createNewUser();
        auth()->loginUsingId($this->user->id);

        $company = $this->createCompanyWithRole(RoleType::ADMIN);

        $project = $this->createProject($company->id);
        $this->setProjectRole($project);

        $ticket = $this->createTicket($project->id);

        $entry_interaction_data[0] = array_merge($entry_interaction_data[0], ['recipient_id' => $recipient_1->id]);
        $entry_interaction_data[1] = array_merge($entry_interaction_data[1], ['recipient_id' => NotifiableGroupType::ALL]);

        $data = [
            'text' => 'test comment text',
            'ticket_id' => $ticket->id,
            'interactions' => [
                'data' => $entry_interaction_data,
            ],
        ];

        //WHEN
        $this->post('/projects/' . $project->id . '/comments?selected_company_id=' . $company->id, $data);

        //THEN
        $this->assertResponseStatus(201);
        /** @var TicketComment $comment */
        $comment = TicketComment::first();

        $this->assertCount(1, Interaction::all());
        /** @var Interaction $interaction */
        $interaction = Interaction::first();

        $this->assertCount(2, InteractionPing::all());
        /** @var InteractionPing $interaction_ping_1 */
        $interaction_ping_1 = InteractionPing::first();
        $this->assertEquals($recipient_1->id, $interaction_ping_1->recipient_id);
        $this->assertEquals('label test 1', $interaction_ping_1->ref);
        $this->assertEquals(NotifiableType::USER, $interaction_ping_1->notifiable);
        $this->assertEquals('message test 1', $interaction_ping_1->message);
        $this->assertEquals($interaction->id, $interaction_ping_1->interaction_id);

        /** @var InteractionPing $interaction_ping_2 */
        $interaction_ping_2 = InteractionPing::all()->last();
        $this->assertEquals(NotifiableGroupType::ALL, $interaction_ping_2->recipient_id);
        $this->assertEquals('label test 2', $interaction_ping_2->ref);
        $this->assertEquals(NotifiableType::GROUP, $interaction_ping_2->notifiable);
        $this->assertEquals('message test 2', $interaction_ping_2->message);
        $this->assertEquals($interaction->id, $interaction_ping_2->interaction_id);
        $this->assertSame(2, DatabaseNotification::query()->count());
    }
}
