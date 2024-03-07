<?php

namespace App\Modules\CalendarAvailability\Models;

use App\Models\Db\UserAvailability;
use Illuminate\Support\Collection;

class DaysOffListDTO
{
    private array $availabilities;

    /**
     * @param UserAvailability []
     */
    public function __construct(array $availabilities)
    {
        $this->availabilities = $availabilities;
    }

    public function getAvailability(): Collection
    {
        return collect($this->availabilities);
    }
}
