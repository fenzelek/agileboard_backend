<?php

declare(strict_types=1);

namespace App\Models\Other;

class InvolvedSourceType
{
    public const TICKET = 'ticket_comments';
    public const KNOWLEDGE_PAGE = 'knowledge_pages';

    public static function all(): array
    {
        return [
            self::TICKET,
            self::KNOWLEDGE_PAGE,
        ];
    }
}
