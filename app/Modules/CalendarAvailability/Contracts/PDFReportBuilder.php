<?php

namespace App\Modules\CalendarAvailability\Contracts;

use App\Models\Db\UserAvailability;

interface PDFReportBuilder
{
    public function getTimestamp(int $month, UserAvailability $availability);

    public function getFreeDays(int $month, UserAvailability $availability, int $amount_free_days);

    public function getOvertime(int $month, UserAvailability $availability);

    public function getMonths(): array;
}
