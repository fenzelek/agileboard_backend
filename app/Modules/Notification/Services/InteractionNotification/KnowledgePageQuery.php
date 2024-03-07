<?php

declare(strict_types=1);

namespace App\Modules\Notification\Services\InteractionNotification;

use App\Models\Db\Knowledge\KnowledgePage;
use App\Models\Db\KnowledgePageComment;

class KnowledgePageQuery
{
    private KnowledgePage $knowledge_page;
    private KnowledgePageComment $page_comment;

    public function __construct(KnowledgePageComment $page_comment, KnowledgePage $knowledge_page)
    {
        $this->knowledge_page = $knowledge_page;
        $this->page_comment = $page_comment;
    }

    public function getPageWithTrashed(int $page_id): ?KnowledgePage
    {
        /** @var ?KnowledgePage */
        return $this->knowledge_page->newQuery()->withTrashed()->find($page_id);
    }

    public function pageExists(int $page_id): bool
    {
        return $this->knowledge_page->newQuery()->where('id', $page_id)->exists();
    }

    public function getComment(int $page_comment_id): ?KnowledgePageComment
    {
        return $this->page_comment->newQuery()->find($page_comment_id);
    }

    public function commentExists(int $page_comment_id): bool
    {
        return $this->page_comment->newQuery()->where('id', $page_comment_id)->exists();
    }
}
