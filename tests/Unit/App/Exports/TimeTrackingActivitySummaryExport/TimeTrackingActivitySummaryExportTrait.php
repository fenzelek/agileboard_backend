<?php

declare(strict_types=1);

namespace Tests\Unit\App\Exports\TimeTrackingActivitySummaryExport;

trait TimeTrackingActivitySummaryExportTrait
{
    public function collectionDataProvider(): array
    {
        return [
            [
                'ticket_id' => 1,
                'ticket_title' => 'Test title',
                'ticket_name' => 'Test name',
                'estimate' => 86400,
                'total_time' => 86461,
                'user_first_name' => 'Test',
                'user_last_name' => 'Test',
                'sprint_name' => 'Some sprint',
                'project_name' => 'Some project',
                'expected_estimate' => '24h',
                'expected_estimate_in_minutes' => 1440,
                'expected_total_time' => '24h 1m',
                'expected_total_time_in_minutes' => 1441,
            ],
        ];
    }
}
