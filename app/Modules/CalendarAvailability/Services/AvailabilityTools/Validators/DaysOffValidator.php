<?php

declare(strict_types=1);

namespace App\Modules\CalendarAvailability\Services\AvailabilityTools\Validators;

use App\Modules\CalendarAvailability\Contracts\AddDaysOffInterface;
use App\Modules\CalendarAvailability\Contracts\AvailabilityStore;
use App\Modules\CalendarAvailability\Contracts\DaysOffInterface;
use App\Modules\CalendarAvailability\Models\DayOffDTO;
use Carbon\Carbon;

class DaysOffValidator
{
    /**
     * @param  DaysOffInterface[]  $days_off
     * @return bool
     */
    public function validate(array $days_off): bool
    {
        foreach ($days_off as $availability) {
            if ($availability->getDate()->startOfDay()->lt(Carbon::today()->subMonth()->startOfDay())) {
                return false;
            }
        }

        return true;

    }
}
