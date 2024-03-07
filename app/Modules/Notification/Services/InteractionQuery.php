<?php

declare(strict_types=1);

namespace App\Modules\Notification\Services;

use App\Models\Db\User;
use App\Models\Other\Interaction\SourceType;
use App\Modules\Notification\Services\InteractionNotification\KnowledgePageQuery;
use App\Modules\Notification\Services\InteractionNotification\TicketQuery;

class InteractionQuery
{
    private KnowledgePageQuery $knowledge_page_query;
    private TicketQuery $ticket_query;
    private User $user;

    public function __construct(User $user, KnowledgePageQuery $knowledge_page_query, TicketQuery $ticket_query)
    {
        $this->user = $user;
        $this->knowledge_page_query = $knowledge_page_query;
        $this->ticket_query = $ticket_query;
    }

    public function findUser(int $user_id): User
    {
        /** @var User */
        return $this->user->newQuery()->findOrFail($user_id);
    }

    public function userExists(int $user_id): bool
    {
        return $this->user->newQuery()->where('id', $user_id)->exists();
    }

    public function sourceExists(string $source_type, int $source_id): bool
    {
        switch ($source_type) {
            case SourceType::TICKET:
                return $this->ticket_query->ticketExists($source_id);
            case SourceType::TICKET_COMMENT:
                return $this->ticket_query->commentExists($source_id);
            case SourceType::KNOWLEDGE_PAGE:
                return $this->knowledge_page_query->pageExists($source_id);
            case SourceType::KNOWLEDGE_PAGE_COMMENT:
                return $this->knowledge_page_query->commentExists($source_id);
        }

        return false;
    }
}
