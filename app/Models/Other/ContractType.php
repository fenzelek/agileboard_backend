<?php

declare(strict_types=1);

namespace App\Models\Other;

class ContractType
{
    const B2B = 'B2B';

    const EMPLOYMENT_CONTRACT = 'Employment contract';

    const MANDATE_CONTRACT = 'Mandate contract';

    const INTERN = 'Intern';

    const OTHER = 'Other';

    public static function all(): array
    {
        return [
            self::B2B,
            self::EMPLOYMENT_CONTRACT,
            self::INTERN,
            self::MANDATE_CONTRACT,
        ];
    }
}
