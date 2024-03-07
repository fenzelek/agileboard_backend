<?php

declare(strict_types=1);

namespace Tests\Feature\App\Modules\Knowledge\Services\KnowledgeInteractionsFactory\ForInvolvedAssigned;

use App\Models\Db\User;
use App\Models\Other\Interaction\ActionType;
use App\Models\Other\Interaction\InteractionEventType;
use App\Models\Other\Interaction\SourceType;
use App\Modules\Knowledge\Services\KnowledgePageInteractionFactory;
use App\Modules\Notification\Notifications\InteractionNotification;
use Event;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class KnowledgeInteractionsFactoryTest extends TestCase
{
    use DatabaseTransactions, KnowledgeInteractionsFactoryTrait;

    private KnowledgePageInteractionFactory $knowledge_interaction_factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->knowledge_interaction_factory = $this->app->make(KnowledgePageInteractionFactory::class);
        Event::fake();
    }

    /**
     * @feature Knowledge
     * @scenario Add/Update new Knowledge Page with new involved list with existing one involved
     * @case New Involved list contains two users
     *
     * @test
     */
    public function forInvolvedAssigned_newInvolvedListContainsTwoUsers(): void
    {
        //GIVEN
        $knowledge_page = $this->createKnowledgePage();
        $user = $this->createNewUser();
        $user_involved_1 = $this->createNewUser();
        $user_involved_2 = $this->createNewUser();
        $company = $this->createCompany();
        $project = $this->createProject(['company_id' => $company->id]);

        $involved = $this->createInvolved([
            'company_id' => $company->id,
            'project_id' => $project->id,
        ]);

        $knowledge_page->involved()->save($involved);

        $new_involved_ids = collect([$user_involved_1->id, $user_involved_2->id]);

        //WHEN
        $this->knowledge_interaction_factory->forInvolvedAssigned(
            $new_involved_ids,
            $knowledge_page,
            $company->id,
            $project->id,
            $user->id
        );

        //THEN
        $this->assertDatabaseCount('interactions', 1);
        $this->assertDatabaseHas('interactions', [
            'user_id' => $user->id,
            'source_type' => SourceType::KNOWLEDGE_PAGE,
            'source_id' => $knowledge_page->id,
            'project_id' => $project->id,
            'company_id' => $company->id,
            'event_type' => InteractionEventType::KNOWLEDGE_PAGE_INVOLVED_ASSIGNED,
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
     * @feature Knowledge
     * @scenario Add/Update new Knowledge Page with new involved list
     * @case Involved list is empty
     *
     * @test
     */
    public function forInvolvedAssigned_involvedListIsEmpty(): void
    {
        //GIVEN
        $knowledge_page = $this->createKnowledgePage();
        $user = $this->createNewUser();
        $company = $this->createCompany();
        $project = $this->createProject(['company_id' => $company->id]);
        $new_involved_ids = collect([]);

        //WHEN
        $this->knowledge_interaction_factory->forInvolvedAssigned(
            $new_involved_ids,
            $knowledge_page,
            $company->id,
            $project->id,
            $user->id
        );

        //THEN
        $this->assertDatabaseCount('interactions', 0);
    }
}
