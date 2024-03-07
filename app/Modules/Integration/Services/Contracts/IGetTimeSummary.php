<?php

namespace App\Modules\Integration\Services\Contracts;

interface IGetTimeSummary
{
    public function getTimeZoneOffset(): int;
}
