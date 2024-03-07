<?php

namespace App\Modules\CalendarAvailability\Models;

use App\Modules\CalendarAvailability\Contracts\AddDaysOffInterface;
use App\Modules\CalendarAvailability\Contracts\UpdateDaysOffInterface;

class AddDaysOffAdapter implements AddDaysOffInterface
{
    private UpdateDaysOffInterface $day_off;

    public function __construct(UpdateDaysOffInterface $update_days_off)
    {
        $this->day_off = $update_days_off;
    }

    public function getSelectedCompanyId(): int
    {
        return $this->day_off->getSelectedCompanyId();
    }

    public function getUserId(): int
    {
        return $this->day_off->getUserId();
    }

    public function getDays(): array
    {
        return $this->day_off->getAddedDays();
    }
}
