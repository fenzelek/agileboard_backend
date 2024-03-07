<?php

declare(strict_types=1);

namespace Tests\Unit\App\Modules\Knowledge\Services\KnowledgePageCommentService;

use App\Models\Db\Knowledge\KnowledgePage;
use App\Models\Db\KnowledgePageComment;
use App\Models\Other\KnowledgePageCommentType;
use App\Modules\Knowledge\Contracts\ICommentCreateRequest;
use App\Modules\Knowledge\Contracts\IUpdateCommentRequest;
use Mockery as m;

trait KnowledgePageCommentServiceTrait
{
    public function commentContentDataProvider(): array
    {
        return [
            'Some example content' => ['text' => 'Some text', 'ref' => 'Ref'],
            'Null content' => ['text' => null, 'ref' => null],
        ];
    }

    protected function mockCreateCommentRequest(int $page_id, string $comment_type, ?string $ref, ?string $text): ICommentCreateRequest
    {
        $request = m::mock(ICommentCreateRequest::class);
        $request->shouldReceive('getKnowledgePageId')->andReturn($page_id);
        $request->shouldReceive('getRef')->andReturn($ref);
        $request->shouldReceive('getText')->andReturn($text);
        $request->shouldReceive('getType')->andReturn($comment_type);

        return $request;
    }

    protected function mockUpdateCommentRequest(int $page_comment_id, string $comment_type, ?string $ref, ?string $text): IUpdateCommentRequest
    {
        $request = m::mock(IUpdateCommentRequest::class);
        $request->shouldReceive('getKnowledgePageCommentId')->andReturn($page_comment_id);
        $request->shouldReceive('getRef')->andReturn($ref);
        $request->shouldReceive('getText')->andReturn($text);
        $request->shouldReceive('getType')->andReturn($comment_type);

        return $request;
    }

    protected function createKnowledgePage(array $attributes=[]): KnowledgePage
    {
        return factory(KnowledgePage::class)->create($attributes);
    }

    protected function createKnowledgePageComment(array $attributes=[]): KnowledgePageComment
    {
        return factory(KnowledgePageComment::class)->create($attributes);
    }
}
