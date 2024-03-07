<?php

namespace App\Modules\CalendarAvailability\Contracts;

interface AvailabilityStore
{
    public function getCompanyId(): int;
    public function getDay();

    /**
     * @return UserAvailabilityInterface[]
     */
    public function getAvailabilities(): array;
}
