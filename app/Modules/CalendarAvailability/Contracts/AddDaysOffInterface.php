<?php

namespace App\Modules\CalendarAvailability\Contracts;

interface AddDaysOffInterface
{
    public function getSelectedCompanyId(): int;
    public function getUserId(): int;

    /**
     * @return DaysOffInterface[]
     */
    public function getDays(): array;
}
