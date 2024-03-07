<?php

namespace Tests\Feature\App\Modules\Agile\Http\Controllers\TicketCommentController\Destroy;

use App\Helpers\ErrorCode;
use App\Models\Db\History;
use App\Models\Db\HistoryField;
use App\Models\Db\Interaction;
use App\Models\Db\InteractionPing;
use App\Models\Db\Project;
use App\Models\Db\TicketComment;
use App\Models\Other\RoleType;
use App\Modules\Agile\Events\DeleteCommentEvent;
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
    public function delete_return_401_when_delete_without_required_role()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = factory(Project::class)->create(['company_id' => $company->id]);
        $project->permission->ticket_comment_destroy = [
            'roles' => [
                ['name' => 'owner', 'value' => true],
            ],
        ];
        $project->permission->save();
        $this->setProjectRole($project);
        $ticket = $this->createTicket($project->id);
        $comment = $this->createTicketComment($ticket->id, $this->user->id);

        $this->delete('/projects/' . $project->id . '/comments/' . $comment->id .
            '?selected_company_id=' . $company->id, [])
            ->seeStatusCode(401);
    }

    /** @test */
    public function delete_error_has_not_permission()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = $this->createProject($company->id);
        $project_2 = factory(Project::class)->create(['company_id' => $company->id]);
        $ticket = $this->createTicket($project_2->id);
        $comment = $this->createTicketComment($ticket->id, $this->user->id);

        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->delete('/projects/' . $project->id . '/comments/' . $comment->id .
            '?selected_company_id=' . $company->id, []);
        $this->verifyErrorResponse(401, ErrorCode::NO_PERMISSION);
    }

    /** @test */
    public function delete_error_comment_not_exist()
    {
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = $this->createProject($company->id);

        $this->delete('/projects/' . $project->id . '/comments/0?selected_company_id=' .
            $company->id, []);
        $this->verifyErrorResponse(404, ErrorCode::RESOURCE_NOT_FOUND);
    }

    /** @test */
    public function delete_success_response()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        Event::fake();

        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = $this->createProject($company->id);
        $ticket = $this->createTicket($project->id);
        $comment = $this->createTicketComment($ticket->id, $this->user->id);
        // manually creating permissions because event is fake
        $project->permission()->create([
            'ticket_comment_destroy' => [
                'roles' => [
                    ['name' => 'admin', 'value' => true],
                ],
            ],
        ]);

        $this->delete('/projects/' . $project->id . '/comments/' . $comment->id .
            '?selected_company_id=' . $company->id, [])
            ->seeStatusCode(204);

        Event::assertDispatched(DeleteCommentEvent::class, function ($e) use ($project, $ticket) {
            if ($e->project->id == $project->id && $e->ticket->id == $ticket->id) {
                return true;
            }
        });
    }

    /** @test */
    public function delete_success_db()
    {
        $now = Carbon::parse('2016-02-03 08:09:10');
        Carbon::setTestNow($now);

        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = $this->createProject($company->id);
        $ticket = $this->createTicket($project->id);
        $comment = $this->createTicketComment($ticket->id, $this->user->id);

        $before_comments = TicketComment::count();
        $before_history = History::count();

        $this->delete('/projects/' . $project->id . '/comments/' . $comment->id .
            '?selected_company_id=' . $company->id, [])
            ->seeStatusCode(204);

        $this->assertSame(null, $comment->fresh());
        $this->assertEquals($before_comments - 1, TicketComment::count());

        //history
        $this->assertEquals($before_history + 1, History::count());
        $history = History::latest('id')->first();
        $this->assertSame($this->user->id, $history->user_id);
        $this->assertSame($comment->ticket_id, $history->resource_id);
        $this->assertSame($comment->id, $history->object_id);
        $this->assertSame(HistoryField::getId(HistoryService::TICKET_COMMENT, 'text'), $history->field_id);
        $this->assertSame($comment->text, $history->value_before);
        $this->assertSame(null, $history->label_before);
        $this->assertSame(null, $history->value_after);
        $this->assertSame(null, $history->label_after);
        $this->assertSame($now->toDateTimeString(), $history->created_at->toDateTimeString());
    }

    /**
     * @feature Ticket
     * @scenario delete Comment ticket
     * @case Comment with interaction and two ping
     *
     * @Expectation Interaction success deleted from db
     *
     * @test
     */
    public function delete_commentWithInteractionAndTwoPing()
    {
        //GIVEN
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = $this->createProject($company->id);

        $ticket = $this->createTicket($project->id);

        $interaction = $this->createInteraction();

        $this->createInteractionPing($interaction->id);
        $this->createInteractionPing($interaction->id);

        $ticket_comment = $this->createTicketComment($ticket->id, $this->user->id);

        $ticket_comment->interactions()->save($interaction);

        //WHEN
        $this->delete('/projects/' . $project->id . '/comments/' . $ticket_comment->id .
            '?selected_company_id=' . $company->id, []);

        //THEN
        $this->assertResponseStatus(204);
        $this->assertCount(0, Interaction::all());
        $this->assertCount(0, InteractionPing::all());
    }

    /**
     * @feature Ticket
     * @scenario delete Comment ticket
     * @case Comment with interaction with ping and another interaction with ping not attached to comment
     *
     * @Expectation Interaction with ping exist in db
     *
     * @test
     */
    public function delete_commentWithInteractionWithPingAndAnotherInteractionWithPingNotAttachedToComment()
    {
        //GIVEN
        $this->createUser();
        auth()->loginUsingId($this->user->id);
        $company = $this->createCompanyWithRole(RoleType::ADMIN);
        $project = $this->createProject($company->id);
        $ticket = $this->createTicket($project->id);

        $interaction_1 = $this->createInteraction();
        $interaction_2 = $this->createInteraction();

        $interaction_ping_1 = $this->createInteractionPing($interaction_1->id);
        $interaction_ping_2 = $this->createInteractionPing($interaction_2->id);

        $comment = $this->createTicketComment($ticket->id, $this->user->id);

        $comment->interactions()->save($interaction_1);

        //WHEN
        $this->delete('/projects/' . $project->id . '/comments/' . $comment->id .
            '?selected_company_id=' . $company->id, []);

        //THEN
        $this->assertResponseStatus(204);
        $this->assertCount(1, Interaction::all());
        $this->assertEquals($interaction_2->id, Interaction::first()->id);

        $this->assertCount(1, InteractionPing::all());
        $this->assertEquals($interaction_ping_2->id, InteractionPing::first()->id);
    }
}
