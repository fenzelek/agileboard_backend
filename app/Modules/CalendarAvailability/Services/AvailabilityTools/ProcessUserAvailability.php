<?php

namespace App\Modules\CalendarAvailability\Services\AvailabilityTools;

use App\Modules\CalendarAvailability\Contracts\AvailabilityStore;
use App\Modules\CalendarAvailability\Contracts\ProcessAvailabilityInterface;
use App\Modules\CalendarAvailability\Models\ProcessListAvailabilityDTO;

class ProcessUserAvailability implements ProcessAvailabilityInterface
{
    public function processAvailability(AvailabilityStore $request): ProcessListAvailabilityDTO
    {
        $permitted_availability = $request->getAvailabilities();

        return new ProcessListAvailabilityDTO($permitted_availability);
    }
}
