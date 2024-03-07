<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Db\Knowledge\KnowledgePage;
use App\Models\Db\KnowledgePageComment;
use App\Models\Db\Project;
use App\Models\Db\User;

class KnowledgePageCommentControllerPolicy extends BasePolicy
{
    protected $group = 'knowledge-page-comment';

    public function store(User $user, Project $project, KnowledgePage $page): bool
    {
        return $this->hasAccessToPage($user, $project, $page);
    }

    public function update(User $user, Project $project, KnowledgePageComment $comment): bool
    {
        return $this->hasAccessToPage($user, $project, $comment->knowledgePage);
    }

    public function destroy(User $user, Project $project, KnowledgePageComment $comment): bool
    {
        return $this->hasAccessToPage($user, $project, $comment->knowledgePage);
    }
}
