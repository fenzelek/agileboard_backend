<?php
declare(strict_types=1);

namespace App\Models\Other;

class MorphMap
{
    const FILES = 'files';
    const KNOWLEDGE_PAGES = 'knowledge_pages';
    const KNOWLEDGE_DIRECTORIES = 'knowledge_directories';
    const KNOWLEDGE_PAGE_COMMENTS = 'knowledge_page_comments';
    const STORIES = 'stories';
    const TICKETS = 'tickets';
    const TICKET_COMMENTS = 'ticket_comments';

    public static function all(): array
    {
        return [
            self::FILES,
            self::KNOWLEDGE_PAGES,
            self::KNOWLEDGE_DIRECTORIES,
            self::KNOWLEDGE_PAGE_COMMENTS,
            self::STORIES,
            self::TICKETS,
            self::TICKET_COMMENTS,
        ];
    }
}
