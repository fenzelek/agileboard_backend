<?php

declare(strict_types=1);

namespace Tests\Feature\App\Modules\Interaction\Services\InteractionManager;

use App\Models\Db\Ticket;
use App\Models\Other\Interaction\ActionType;
use App\Models\Other\Interaction\InteractionEventType;
use App\Models\Other\Interaction\NotifiableType;
use App\Models\Other\Interaction\SourceType;
use App\Modules\Agile\Http\Requests\InteractionPingRequest;
use App\Modules\Interaction\Models\Dto\InteractionDTO;
use App\Modules\Interaction\Services\InteractionManager;
use App\Modules\Notification\Models\DatabaseNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InteractionManagerTest extends TestCase
{
    use DatabaseTransactions;
    use InteractionManagerTrait;

    private InteractionManager $interaction_manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->interaction_manager = $this->app->make(InteractionManager::class);
        Ticket::unsetEventDispatcher();
    }

    /**
     * @feature Interaction
     * @scenario User create interaction
     * @case Interaction was created
     *
     * @test
     */
    public function addNotifications_ShouldAddNotificationsToDatabase(): void
    {
        //GIVEN
        DatabaseNotification::query()->delete();
        $user = $this->createNewUser();
        $interaction_model = $this->createInteraction([
            'action_type' => ActionType::PING,
            'event_type' => InteractionEventType::TICKET_COMMENT_NEW,
            'source_type' => SourceType::TICKET_COMMENT,
        ]);
        $interaction_ping = new InteractionPingRequest([
            'recipient_id' => $user->id,
            'ref' => '#ref',
            'notifiable' => NotifiableType::USER,
            'message' => 'Some message',
        ]);
        $interaction = new InteractionDTO(
            $interaction_model,
            $this->mockRequest(collect([$interaction_ping]))
        );

        //WHEN
        $this->interaction_manager->addNotifications($interaction);
        /** @var DatabaseNotification $notification */
        $notification = DatabaseNotification::query()->first();

        //THEN
        $this->assertDatabaseCount('notifications', 1);
        $this->assertSame($interaction_model->company_id, $notification->company_id);
        $this->assertSame([
            'project_id' => $interaction_model->project_id,
            'author_id' => $interaction_model->user_id,
            'action_type' => $interaction_model->action_type,
            'event_type' => $interaction_model->event_type,
            'source_type' => SourceType::TICKET_COMMENT,
            'source_id' => $interaction_model->source_id,
            'ref' => '#ref',
            'message' => 'Some message',
        ], $notification->data);
    }
}
