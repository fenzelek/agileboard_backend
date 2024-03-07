<?php

namespace App\Modules\CalendarAvailability\Contracts;

use App\Modules\CalendarAvailability\Models\ProcessListAvailabilityDTO;

interface AdderAvailabilityInterface
{
    public function add(ProcessListAvailabilityDTO $availability, int $company_id, string $day);
}
