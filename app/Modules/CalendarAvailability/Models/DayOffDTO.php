<?php

namespace App\Modules\CalendarAvailability\Models;

use App\Models\Db\UserAvailability;
use App\Modules\CalendarAvailability\Contracts\DaysOffInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DayOffDTO implements DaysOffInterface
{
    private string $date;
    private string $description;

    public function __construct(string $date, string $description)
    {
        $this->date = $date;
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDate(): Carbon
    {
        return Carbon::parse($this->date);
    }


}
