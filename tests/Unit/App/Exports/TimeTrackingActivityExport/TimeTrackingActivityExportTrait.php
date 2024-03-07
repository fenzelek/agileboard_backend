<?php

declare(strict_types=1);

namespace Tests\Unit\App\Exports\TimeTrackingActivityExport;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

trait TimeTrackingActivityExportTrait
{
    public function collectionDataProvider(): array
    {
        return [
            [
                'user_first_name' => 'Paweł',
                'user_last_name' => 'Kowalski',
                'project_name' => 'Agile board',
                'sprint_name' => 'Agile board',
                'ticket_title' => 'AB-1222',
                'comment' => 'Czas manualny',
                'tracked_time' => 30,
                'utc_started_at' => '2023-03-16 08:33',
                'utc_finished_at' => '2023-03-18 08:33',
                'utc_offset' => 2,
                'started_at_in_timezone' => Date::dateTimeToExcel(Carbon::parse('2023-03-16 10:33')),
                'finished_at_in_timezone' => Date::dateTimeToExcel(Carbon::parse('2023-03-18 10:33')),
                'expected_tracked_time' => '0m',
                'expected_tracked_time_in_minutes' => 0,
            ],
            [
                'user_first_name' => 'Paweł',
                'user_last_name' => 'Kowalski',
                'project_name' => 'Agile board',
                'sprint_name' => 'Agile board',
                'ticket_title' => 'AB-1222',
                'comment' => 'Czas manualny',
                'tracked_time' => 3661,
                'utc_started_at' => '2023-03-16 08:33',
                'utc_finished_at' => '2023-03-18 08:33',
                'utc_offset' => 2,
                'started_at_in_timezone' => Date::dateTimeToExcel(Carbon::parse('2023-03-16 10:33')),
                'finished_at_in_timezone' => Date::dateTimeToExcel(Carbon::parse('2023-03-18 10:33')),
                'expected_tracked_time' => '1h 1m',
                'expected_tracked_time_in_minutes' => 61,
            ],
        ];
    }
}
