<?php

namespace App\Modules\Integration\Services\Contracts;

use Carbon\CarbonInterface;

interface DailyActivityEntryData
{
    public function getUserId(): int;

    public function getCompanyId(): int;

    public function getStartedAt():CarbonInterface;
    public function getFinishedAt():CarbonInterface;

    public function getTimeZoneOffset(): int;
}
