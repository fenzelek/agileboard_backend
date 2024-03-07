<?php

namespace App\Modules\CalendarAvailability\Contracts;

use Carbon\Carbon;
use Carbon\CarbonInterface;

interface DeleteDaysOffInterface
{
    public function getSelectedCompanyId(): int;
    public function getUserId(): int;

    /**
     * @return CarbonInterface[]
     */
    public function getDays(): array;
}
