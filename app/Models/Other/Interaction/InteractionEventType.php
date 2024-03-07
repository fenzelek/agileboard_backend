<?php

declare(strict_types=1);

namespace App\Models\Other\Interaction;

class InteractionEventType
{
    const TICKET_COMMENT_NEW = 'ticket_comment_new';
    const TICKET_COMMENT_EDIT = 'ticket_comment_edit';
    const TICKET_NEW = 'ticket_new';
    const TICKET_EDIT = 'ticket_edit';
    const KNOWLEDGE_PAGE_NEW = 'knowledge_page_new';
    const KNOWLEDGE_PAGE_EDIT = 'knowledge_page_edit';
    const KNOWLEDGE_PAGE_COMMENT_NEW = 'knowledge_page_comment_new';
    const KNOWLEDGE_PAGE_COMMENT_EDIT = 'knowledge_page_comment_edit';
    const KNOWLEDGE_PAGE_COMMENT_DELETE = 'knowledge_page_comment_delete';
    const KNOWLEDGE_PAGE_INVOLVED_ASSIGNED = 'knowledge_page_involved_assigned';
    const KNOWLEDGE_PAGE_INVOLVED_DELETED = 'knowledge_page_involved_deleted';
    const TICKET_COMMENT_DELETE = 'ticket_comment_delete';
    const TICKET_INVOLVED_ASSIGNED = 'ticket_involved_assigned';
    const TICKET_INVOLVED_DELETED = 'ticket_involved_deleted';

    public static function all(): array
    {
        return [
            self::KNOWLEDGE_PAGE_NEW,
            self::KNOWLEDGE_PAGE_EDIT,
            self::KNOWLEDGE_PAGE_COMMENT_NEW,
            self::KNOWLEDGE_PAGE_COMMENT_EDIT,
            self::KNOWLEDGE_PAGE_COMMENT_DELETE,
            self::KNOWLEDGE_PAGE_INVOLVED_ASSIGNED,
            self::KNOWLEDGE_PAGE_INVOLVED_DELETED,
            self::TICKET_COMMENT_NEW,
            self::TICKET_COMMENT_EDIT,
            self::TICKET_COMMENT_DELETE,
            self::TICKET_INVOLVED_ASSIGNED,
            self::TICKET_INVOLVED_DELETED,
            self::TICKET_NEW,
            self::TICKET_EDIT,
        ];
    }
}
