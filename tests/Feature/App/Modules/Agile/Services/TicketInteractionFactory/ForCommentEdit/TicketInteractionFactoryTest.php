<?php

declare(strict_types=1);

namespace Tests\Feature\App\Modules\Agile\Services\TicketInteractionFactory\ForCommentEdit;

use App\Models\Db\User;
use App\Models\Other\Interaction\ActionType;
use App\Models\Other\Interaction\InteractionEventType;
use App\Models\Other\Interaction\SourceType;
use App\Modules\Agile\Services\TicketInteractionFactory;
use Event;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TicketInteractionFactoryTest extends TestCase
{
    use DatabaseTransactions, TicketInteractionFactoryTrait;

    private TicketInteractionFactory $ticket_interaction_factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ticket_interaction_factory = $this->app->make(TicketInteractionFactory::class);
        Event::fake();
    }

    /**
     * @feature Ticket
     * @scenario Update ticket's comment
     * @case Valid interaction pings
     *
     * @test
     */
    public function ForCommentEdit_validInteractionPings(): void
    {
        //GIVEN
        $comment = $this->createComment();
        $author = $this->createNewUser();
        $recipient = $this->createNewUser();
        $company = $this->createCompany();
        $project = $this->createProject(['company_id' => $company->id]);
        $request = $this->mockUpdateCommentRequest($project->id, $recipient->id);

        //WHEN
        $this->ticket_interaction_factory->forCommentEdit($request, $comment, $project->id, $author->id);

        //THEN
        $this->assertDatabaseCount('interactions', 1);
        $this->assertDatabaseHas('interactions', [
            'user_id' => $author->id,
            'source_type' => SourceType::TICKET_COMMENT,
            'source_id' => $comment->id,
            'project_id' => $project->id,
            'company_id' => $company->id,
            'event_type' => InteractionEventType::TICKET_COMMENT_EDIT,
            'action_type' => ActionType::PING,
        ]);
        $this->assertDatabaseCount('interaction_pings', 1);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $recipient->id,
        ]);
    }

    /**
     * @feature Ticket
     * @scenario Update ticket's comment
     * @case No pings with updated ticket comment
     *
     * @test
     */
    public function ForCommentEdit_noPingsWithUpdatedTicketComment(): void
    {
        //GIVEN
        $comment = $this->createComment();
        $author = $this->createNewUser();
        $recipient = $this->createNewUser();
        $company = $this->createCompany();
        $project = $this->createProject(['company_id' => $company->id]);
        $request = $this->mockUpdateCommentRequest($project->id, $recipient->id, false);

        //WHEN
        $this->ticket_interaction_factory->forCommentEdit($request, $comment, $project->id, $author->id);

        //THEN
        $this->assertDatabaseCount('interactions', 0);
    }
}
