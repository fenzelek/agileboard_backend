<?php

declare(strict_types=1);

namespace Tests\Feature\App\Modules\Agile\Services\TicketInteractionFactory\ForTicketEdit;

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
     * @scenario Update ticket
     * @case Interaction valid
     *
     * @test
     */
    public function forTicketEdit_ShouldSendNewCommentInteraction(): void
    {
        //GIVEN
        $ticket = $this->createTicket();
        $author = $this->createNewUser();
        $recipient = $this->createNewUser();
        $company = $this->createCompany();
        $project = $this->createProject(['company_id' => $company->id]);
        $request = $this->mockUpdateCommentRequest($project->id, $recipient->id);

        //WHEN
        $this->ticket_interaction_factory->forTicketEdit($request, $ticket, $project->id, $author->id);

        //THEN
        $this->assertDatabaseCount('interactions', 1);
        $this->assertDatabaseHas('interactions', [
            'user_id' => $author->id,
            'source_type' => SourceType::TICKET,
            'source_id' => $ticket->id,
            'project_id' => $project->id,
            'company_id' => $company->id,
            'event_type' => InteractionEventType::TICKET_EDIT,
            'action_type' => ActionType::PING,
        ]);
        $this->assertDatabaseCount('interaction_pings', 1);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $recipient->id,
        ]);
    }

    /**
     * @feature Knowledge
     * @scenario Update ticket
     * @case No pings with updated ticket
     *
     * @test
     */
    public function forTicketEdit_noPingsWithUpdatedTicket(): void
    {
        //GIVEN
        $knowledge_page = $this->createTicket();
        $author = $this->createNewUser();
        $recipient = $this->createNewUser();
        $company = $this->createCompany();
        $project = $this->createProject(['company_id' => $company->id]);
        $request = $this->mockUpdateCommentRequest($project->id, $recipient->id, false);

        //WHEN
        $this->ticket_interaction_factory->forTicketEdit($request, $knowledge_page, $project->id, $author->id);

        //THEN
        $this->assertDatabaseCount('interactions', 0);
    }
}
