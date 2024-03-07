<?php

declare(strict_types=1);

namespace App\Modules\CalendarAvailability\Services\AvailabilityTools\Validators;

use App\Modules\CalendarAvailability\Contracts\AvailabilityStore;

class ServeAvailabilityValidator
{
    public function validate(AvailabilityStore $availability_provider): bool
    {
        if (! $this->availabilityWrongPeriod($availability_provider)) {
            return false;
        }

        if (! $this->availabilityOverlap($availability_provider)) {
            return false;
        }

        return true;
    }

    private function availabilityWrongPeriod(AvailabilityStore $availability_provider): bool
    {
        foreach ($availability_provider->getAvailabilities() as $availability) {
            if ($availability->getStartTime() > $availability->getStopTime()) {
                return false;
            }
        }

        return true;
    }

    private function availabilityOverlap(AvailabilityStore $availability_provider): bool
    {
        $availabilities = $availability_provider->getAvailabilities();
        do {
            $first = array_shift($availabilities);
            foreach ($availabilities as $availability) {
                $next_time_start = $availability->getStartTime() ?? false;
                $next_time_stop = $availability->getStopTime() ?? false;

                //start time inside next time
                //zmień tablicę na obiekt
                if ($first->getStartTime() > $next_time_start &&
                    $first->getStartTime() < $next_time_stop) {
                    return false;
                }

                //stop time inside next time
                if ($first->getStopTime() > $next_time_start &&
                    $first->getStopTime() < $next_time_stop) {
                    return false;
                }

                //cover or same time
                if ($first->getStartTime() <= $next_time_start &&
                    $first->getStopTime()  >= $next_time_stop) {
                    return false;
                }
            }
        } while (! empty($availabilities));

        return true;
    }
}
