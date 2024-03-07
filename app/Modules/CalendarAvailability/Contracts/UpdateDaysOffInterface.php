<?php

namespace App\Modules\CalendarAvailability\Contracts;

use App\Modules\CalendarAvailability\Models\DayOffDTO;
use Carbon\Carbon;
use Carbon\CarbonInterface;

interface UpdateDaysOffInterface
{
    public function getSelectedCompanyId(): int;
    public function getUserId(): int;

    /**
     * @return DaysOffInterface[]
     */
    public function getAddedDays(): array;

    /**
     * @return CarbonInterface[]
     */
    public function getDeletedDays(): array;

}
