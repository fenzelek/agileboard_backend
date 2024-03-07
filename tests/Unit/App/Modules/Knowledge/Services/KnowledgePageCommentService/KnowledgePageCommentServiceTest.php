<?php

declare(strict_types=1);

namespace Tests\Unit\App\Modules\Knowledge\Services\KnowledgePageCommentService;

use App\Models\Other\KnowledgePageCommentType;
use App\Modules\Knowledge\Services\KnowledgePageCommentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class KnowledgePageCommentServiceTest extends TestCase
{
    use DatabaseTransactions;
    use KnowledgePageCommentServiceTrait;

    private KnowledgePageCommentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(KnowledgePageCommentService::class);
    }

    /**
     * @feature Knowledge page comments
     * @scenario Store comment
     * @case Valid data
     *
     * @dataProvider commentContentDataProvider
     * @test
     */
    public function create_ShouldCreateComment(?string $ref, ?string $text): void
    {
        //GIVEN
        $page = $this->createKnowledgePage();
        $user_id = $page->creator_id;
        $type = KnowledgePageCommentType::INTERNAL;
        $request = $this->mockCreateCommentRequest($page->id, $type, $ref, $text);

        //WHEN
        $result = $this->service->create($request, $user_id);

        //THEN
        $this->assertSame($page->id, $result->knowledge_page_id);
        $this->assertSame($user_id, $result->user_id);
        $this->assertSame($ref, $result->ref);
        $this->assertSame($text, $result->text);
        $this->assertSame($type, $result->type);
    }

    /**
     * @feature Knowledge page comments
     * @scenario Update comment
     * @case Valid data
     *
     * @dataProvider commentContentDataProvider
     *
     * @test
     */
    public function update_ShouldUpdateComment(?string $ref, ?string $text): void
    {
        //GIVEN
        $comment = $this->createKnowledgePageComment();
        $request = $this->mockUpdateCommentRequest($comment->id, KnowledgePageCommentType::INTERNAL, $ref, $text);

        //WHEN
        $result = $this->service->update($request);

        //THEN
        $this->assertSame($comment->id, $result->id);
        $this->assertSame($comment->user_id, $result->user_id);
        $this->assertSame($ref, $result->ref);
        $this->assertSame($text, $result->text);
        $this->assertSame((string) ($comment->type), $result->type);
    }
}
