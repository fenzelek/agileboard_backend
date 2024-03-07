<?php

declare(strict_types=1);

namespace Tests\Feature\App\Modules\Knowledge\Services\KnowledgeInteractionsFactory\ForCommentEdit;

use App\Models\Db\User;
use App\Models\Other\Interaction\ActionType;
use App\Models\Other\Interaction\InteractionEventType;
use App\Models\Other\Interaction\SourceType;
use App\Modules\Knowledge\Services\KnowledgePageInteractionFactory;
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
     * @scenario Update knowledge page comment
     * @case Interaction valid
     *
     * @test
     */
    public function forCommentEdit_ShouldSendNewCommentInteraction(): void
    {
        //GIVEN
        $comment = $this->createComment();
        $author = $this->createNewUser();
        $recipient = $this->createNewUser();
        $company = $this->createCompany();
        $project = $this->createProject(['company_id' => $company->id]);
        $request = $this->mockUpdateCommentRequest($project->id, $recipient->id);

        //WHEN
        $this->knowledge_interaction_factory->forCommentEdit($request, $comment, $author->id);

        //THEN
        $this->assertDatabaseCount('interactions', 1);
        $this->assertDatabaseHas('interactions', [
            'user_id' => $author->id,
            'source_type' => SourceType::KNOWLEDGE_PAGE_COMMENT,
            'source_id' => $comment->id,
            'project_id' => $project->id,
            'company_id' => $company->id,
            'event_type' => InteractionEventType::KNOWLEDGE_PAGE_COMMENT_EDIT,
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
     * @scenario Update knowledge page comment
     * @case No pings with updated knowledge page comment
     *
     * @test
     */
    public function forCommentEdit_noPingsWithUpdatedKnowledgePage(): void
    {
        //GIVEN
        $comment = $this->createComment();
        $author = $this->createNewUser();
        $recipient = $this->createNewUser();
        $company = $this->createCompany();
        $project = $this->createProject(['company_id' => $company->id]);
        $request = $this->mockUpdateCommentRequest($project->id, $recipient->id, false);

        //WHEN
        $this->knowledge_interaction_factory->forCommentEdit($request, $comment, $author->id);

        //THEN
        $this->assertDatabaseCount('interactions', 0);
    }
}
