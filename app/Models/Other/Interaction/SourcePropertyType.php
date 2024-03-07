<?php

declare(strict_types=1);

namespace App\Models\Other\Interaction;

class SourcePropertyType
{
    public const TICKET = 'ticket';
    public const TICKET_COMMENT = 'ticket_comment';
    public const KNOWLEDGE_PAGE = 'knowledge_page';
    public const KNOWLEDGE_PAGE_COMMENT = 'knowledge_page_comment';

    public static function all(): array
    {
        return [
            self::TICKET,
            self::TICKET_COMMENT,
            self::KNOWLEDGE_PAGE,
            self::KNOWLEDGE_PAGE_COMMENT,
        ];
    }
}
