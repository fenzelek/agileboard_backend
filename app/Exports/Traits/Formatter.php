<?php

declare(strict_types=1);

namespace App\Exports\Traits;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

trait Formatter
{
    public function formatSeconds(int $sec): string
    {
        $hours = (int) floor($sec/3600);
        $minutes = ($sec/60)%60;

        if ($hours===0) {
            return "{$minutes}m";
        }
        if ($minutes===0) {
            return "{$hours}h";
        }

        return "{$hours}h {$minutes}m";
    }

    public function getMinutesFromSeconds(int $seconds): int
    {
        return (int) floor($seconds/60);
    }

    public function formatUtcDateForExcel(Carbon $utc_date_time, int $offset): float
    {
        return Date::dateTimeToExcel($utc_date_time->addHours($offset));
    }
}

