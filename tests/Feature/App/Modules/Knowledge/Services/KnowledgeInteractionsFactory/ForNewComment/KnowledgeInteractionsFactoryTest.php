<?php

declare(strict_types=1);

namespace Tests\Feature\App\Modules\Knowledge\Services\KnowledgeInteractionsFactory\ForNewComment;

use Event;
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
        Event::fake();
    }

    /**
     * @feature Knowledge
     * @scenario Add new knowledge page comment
     * @case Interaction valid
     *
     * @test
     */
    public function forNewComment_ShouldSendNewCommentInteraction(): void
    {
        //GIVEN
        $comment = $this->createComment();
        $author = $this->createNewUser();
        $recipient = $this->createNewUser();
        $company = $this->createCompany();
        $project = $this->createProject(['company_id' => $company->id]);
        $request = $this->mockCreateCommentRequest($project->id, $recipient->id);

        //WHEN
        $this->knowledge_interaction_factory->forNewComment($request, $comment, $author->id);

        //THEN
        $this->assertDatabaseCount('interactions', 1);
        $this->assertDatabaseHas('interactions', [
            'user_id' => $author->id,
            'source_type' => SourceType::KNOWLEDGE_PAGE_COMMENT,
            'source_id' => $comment->id,
            'project_id' => $project->id,
            'company_id' => $company->id,
            'event_type' => InteractionEventType::KNOWLEDGE_PAGE_COMMENT_NEW,
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
     * @case No pings with new knowledge page comment
     *
     * @test
     */
    public function forNewComment_noPingsWithNewKnowledgePageComment(): void
    {
        //GIVEN
        $comment = $this->createComment();
        $author = $this->createNewUser();
        $recipient = $this->createNewUser();
        $company = $this->createCompany();
        $project = $this->createProject(['company_id' => $company->id]);
        $request = $this->mockCreateCommentRequest($project->id, $recipient->id, false);

        //WHEN
        $this->knowledge_interaction_factory->forNewComment($request, $comment, $author->id);

        //THEN
        $this->assertDatabaseCount('interactions', 0);
    }
}
