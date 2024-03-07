<?php

declare(strict_types=1);

namespace App\Modules\Knowledge\Services;

use App\Models\Db\KnowledgePageComment;
use App\Modules\Knowledge\Contracts\ICommentCreateRequest;
use App\Modules\Knowledge\Contracts\IUpdateCommentRequest;
use App\Modules\Knowledge\Models\Dto\UpdateKnowledgePageCommentDto;

class KnowledgePageCommentService
{
    private KnowledgePageComment $knowledge_page_comment;

    public function __construct(KnowledgePageComment $knowledge_page_comment)
    {
        $this->knowledge_page_comment = $knowledge_page_comment;
    }

    public function create(ICommentCreateRequest $request, int $user_id): KnowledgePageComment
    {
        return $this->knowledge_page_comment->newQuery()
            ->create([
                'knowledge_page_id' => $request->getKnowledgePageId(),
                'user_id' => $user_id,
                'type' => $request->getType(),
                'ref' => $request->getRef(),
                'text' => $request->getText(),
            ]);
    }

    public function update(IUpdateCommentRequest $request): KnowledgePageComment
    {
        $this->knowledge_page_comment->newQuery()
            ->where('id', $request->getKnowledgePageCommentId())
            ->update([
                'ref' => $request->getRef(),
                'text' => $request->getText(),
            ]);

        return $this->knowledge_page_comment->newQuery()->find($request->getKnowledgePageCommentId());
    }

    public function destroy(int $knowledge_page_comment_id): void
    {
        $this->knowledge_page_comment->newQuery()
            ->where('id', $knowledge_page_comment_id)
            ->delete();
    }
}
