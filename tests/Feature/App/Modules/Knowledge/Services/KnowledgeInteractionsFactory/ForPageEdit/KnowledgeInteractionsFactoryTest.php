<?php

declare(strict_types=1);

namespace Tests\Feature\App\Modules\Knowledge\Services\KnowledgeInteractionsFactory\ForPageEdit;

use App\Models\Db\User;
use App\Models\Other\Interaction\ActionType;
use App\Models\Other\Interaction\InteractionEventType;
use App\Models\Other\Interaction\SourceType;
use App\Modules\Knowledge\Services\KnowledgePageInteractionFactory;
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
    }

    /**
     * @feature Knowledge
     * @scenario Update knowledge page
     * @case Interaction valid
     *
     * @test
     */
    public function forPageEdit_ShouldSendNewCommentInteraction(): void
    {
        //GIVEN
        $knowledge_page = $this->createKnowledgePage();
        $author = $this->createNewUser();
        $recipient = $this->createNewUser();
        $company = $this->createCompany();
        $project = $this->createProject(['company_id' => $company->id]);
        $request = $this->mockUpdateCommentRequest($project->id, $recipient->id);

        //WHEN
        $this->knowledge_interaction_factory->forPageEdit($request, $knowledge_page, $project->id, $author->id);

        //THEN
        $this->assertDatabaseCount('interactions', 1);
        $this->assertDatabaseHas('interactions', [
            'user_id' => $author->id,
            'source_type' => SourceType::KNOWLEDGE_PAGE,
            'source_id' => $knowledge_page->id,
            'project_id' => $project->id,
            'company_id' => $company->id,
            'event_type' => InteractionEventType::KNOWLEDGE_PAGE_EDIT,
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
     * @scenario Update knowledge page
     * @case No pings with updated knowledge page
     *
     * @test
     */
    public function forPageEdit_noPingsWithUpdatedKnowledgePage(): void
    {
        //GIVEN
        $knowledge_page = $this->createKnowledgePage();
        $author = $this->createNewUser();
        $recipient = $this->createNewUser();
        $company = $this->createCompany();
        $project = $this->createProject(['company_id' => $company->id]);
        $request = $this->mockUpdateCommentRequest($project->id, $recipient->id, false);

        //WHEN
        $this->knowledge_interaction_factory->forPageEdit($request, $knowledge_page, $project->id, $author->id);

        //THEN
        $this->assertDatabaseCount('interactions', 0);
    }
}
