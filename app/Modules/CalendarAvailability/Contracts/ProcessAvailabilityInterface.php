<?php

namespace App\Modules\CalendarAvailability\Contracts;

use App\Modules\CalendarAvailability\Models\ProcessListAvailabilityDTO;

interface ProcessAvailabilityInterface
{
    public function processAvailability(AvailabilityStore $request): ProcessListAvailabilityDTO;
}
