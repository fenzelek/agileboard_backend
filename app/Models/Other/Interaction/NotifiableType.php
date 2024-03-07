<?php

declare(strict_types=1);

namespace App\Models\Other\Interaction;

class NotifiableType
{
    const GROUP = 'group';
    const USER = 'user';

    public static function all(): array
    {
        return [
            self::GROUP,
            self::USER,
        ];
    }
}
