<?php

namespace App\Modules\CalendarAvailability\Contracts;

use Carbon\CarbonInterface;

interface DaysOffInterface
{
    public function getDate(): CarbonInterface;

    public function getDescription(): string;
}
