<?php

declare(strict_types=1);

namespace App\Models\Other\Interaction;

class ActionType
{
    const PING = 'ping';
    const COMMENT = 'comment';
    const INVOLVED = 'involved';

    public static function all(): array
    {
        return [
            self::PING,
            self::COMMENT,
            self::INVOLVED,
        ];
    }
}
