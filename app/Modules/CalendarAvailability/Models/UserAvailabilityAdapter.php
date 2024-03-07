<?php

namespace App\Modules\CalendarAvailability\Models;

use App\Modules\CalendarAvailability\Contracts\UserAvailabilityInterface;
use Illuminate\Support\Collection;

class UserAvailabilityAdapter implements UserAvailabilityInterface
{
    private DayOffDTO $day_off;

    public function __construct(DayOffDTO $day_off)
    {
        $this->day_off = $day_off;
    }

    public function getStartTime(): string
    {
        return $this->day_off->getDate()->startOfDay()->format('Y-m-d');
    }

    public function getStopTime(): string
    {
        return $this->day_off->getDate()->endOfDay()->format('Y-m-d');
    }

    public function getOvertime(): bool
    {
        return false;
    }

    public function getAvailable(): bool
    {
        return false;
    }

    public function getDescription(): string
    {
        return $this->day_off->getDescription();
    }

    public function getSource(): string
    {
        return UserAvailabilitySourceType::EXTERNAL;
    }
}
