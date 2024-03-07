<?php

declare(strict_types=1);

namespace App\Models\Other;

class DepartmentType
{
    const DEVELOPERS = 'Developers';

    const TELECOMMUNICATION = 'Telecommunication';

    const OTHER = 'Other';

    public static function all(): array
    {
        return [
            self::DEVELOPERS,
            self::TELECOMMUNICATION,
            self::OTHER,
        ];
    }
}
