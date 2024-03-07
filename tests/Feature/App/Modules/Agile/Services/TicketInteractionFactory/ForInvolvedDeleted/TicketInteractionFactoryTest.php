<?php

declare(strict_types=1);

namespace Tests\Feature\App\Modules\Agile\Services\TicketInteractionFactory\ForInvolvedDeleted;

use App\Models\Db\User;
use App\Models\Other\Interaction\ActionType;
use App\Models\Other\Interaction\InteractionEventType;
use App\Models\Other\Interaction\SourceType;
use App\Modules\Agile\Services\TicketInteractionFactory;
use App\Modules\Notification\Notifications\InteractionNotification;
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
     * @scenario Delete Ticket with new involved list
     * @case Deleted Ticket contains two position on Involved list
     *
     * @test
     */
    public function forInvolvedDeleted_deletedTicketContainsTwoPositionOnInvolvedList(): void
    {
        //GIVEN
        $ticket = $this->createTicket();
        $user = $this->createNewUser();
        $user_involved_1 = $this->createNewUser();
        $user_involved_2 = $this->createNewUser();
        $company = $this->createCompany();
        $project = $this->createProject(['company_id' => $company->id]);

        $involved_1 = $this->createInvolved([
            'company_id' => $company->id,
            'project_id' => $project->id,
        ]);

        $involved_2 = $this->createInvolved([
            'company_id' => $company->id,
            'project_id' => $project->id,
        ]);

        $ticket->involved()->save($involved_1);
        $ticket->involved()->save($involved_2);

        $ticket->delete();

        $involved_ids = collect([$user_involved_1->id, $user_involved_2->id]);

        //WHEN
        $this->ticket_interaction_factory->forInvolvedDeleted(
            $involved_ids,
            $ticket,
            $project,
            $user->id
        );

        //THEN
        $this->assertDatabaseCount('interactions', 1);
        $this->assertDatabaseHas('interactions', [
            'user_id' => $user->id,
            'source_type' => SourceType::TICKET,
            'source_id' => $ticket->id,
            'project_id' => $project->id,
            'company_id' => $company->id,
            'event_type' => InteractionEventType::TICKET_INVOLVED_DELETED,
            'action_type' => ActionType::INVOLVED,
        ]);
        $this->assertDatabaseCount('interaction_pings', 2);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $user_involved_1->id,
            'company_id' => $company->id,
            'type' => InteractionNotification::class,
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $user_involved_2->id,
            'company_id' => $company->id,
            'type' => InteractionNotification::class,
        ]);
    }

    /**
     * @feature Ticket
     * @scenario Delete Ticket with new involved list
     * @case Involved list is empty
     *
     * @test
     */
    public function forInvolvedDeleted_involvedListIsEmpty(): void
    {
        //GIVEN
        $ticket = $this->createTicket();
        $user = $this->createNewUser();
        $company = $this->createCompany();
        $project = $this->createProject(['company_id' => $company->id]);
        $involved_ids = collect([]);

        //WHEN
        $this->ticket_interaction_factory->forInvolvedDeleted(
            $involved_ids,
            $ticket,
            $project,
            $user->id
        );

        //THEN
        $this->assertDatabaseCount('interactions', 0);
    }
}
