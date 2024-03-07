<?php

declare(strict_types=1);

namespace App\Models\Other;

class NotificationType
{
    public const INTERACTION = 'interaction';

    public static function all(): array
    {
        return [
            self::INTERACTION,
        ];
    }
}
