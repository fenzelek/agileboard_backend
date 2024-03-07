<?php

declare(strict_types=1);

namespace Tests\Unit\App\Exports\SprintExport;

trait SprintExportTrait
{
    public function fromCollectionProvider(): array
    {
        return [
            [
                'ticket_id' => 1,
                'user_first_name' => 'Paweł',
                'user_last_name' => 'Kowalski',
                'ticket_title' => 'AB-1250',
                'ticket_name' => 'Agile board',
                'ticket_estimated_seconds' => 70,
                'ticket_tracked_seconds' => 3660,
                'expected_index' => 1,
                'expected_estimate_time' => '1m',
                'expected_estimate_time_in_minutes' => 1,
                'expected_tracked_time' => '1h 1m',
                'expected_tracked_time_in_minutes' => 61,
            ],
        ];
    }
}
