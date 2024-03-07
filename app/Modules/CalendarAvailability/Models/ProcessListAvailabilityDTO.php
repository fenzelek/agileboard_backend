<?php

namespace App\Modules\CalendarAvailability\Models;

use App\Modules\CalendarAvailability\Contracts\UserAvailabilityInterface;
use Illuminate\Support\Collection;

class ProcessListAvailabilityDTO
{
    /**
     * @var UserAvailabilityInterface[]
     */
    private array $availabilities;

    /**
     * @param  UserAvailabilityInterface[] $availabilities
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
