<?php

declare(strict_types=1);

namespace App\Models\Other\Interaction;

class NotifiableGroupType
{
    const ALL = 1;
    const INVOLVED = 2;

    public static function all(): array
    {
        return [
            self::ALL,
            self::INVOLVED,
        ];
    }
}
