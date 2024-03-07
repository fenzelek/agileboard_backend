<?php

declare(strict_types=1);

namespace App\Models\Other\Interaction;

use App\Models\Other\MorphMap;

class SourceType
{
    const TICKET = MorphMap::TICKETS;
    const TICKET_COMMENT = MorphMap::TICKET_COMMENTS;
    const KNOWLEDGE_PAGE = MorphMap::KNOWLEDGE_PAGES;
    const KNOWLEDGE_PAGE_COMMENT = MorphMap::KNOWLEDGE_PAGE_COMMENTS;

    /** @return string[] */
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
