<?php

declare(strict_types=1);

namespace Tests\Unit\App\Exports\TimeTrackingActivitySummaryExport;

use App\Exports\TimeTrackingActivitySummaryExport;
use App\Modules\Integration\Models\ActivitySummaryExportDto;
use Tests\TestCase;

class TimeTrackingActivitySummaryExportTest extends TestCase
{
    use TimeTrackingActivitySummaryExportTrait;

    /**
     * @test
     * @dataProvider collectionDataProvider
     */
    public function collection_ShouldReturnExpectedTrackingSummary(
        int $ticket_id,
        string $ticket_title,
        string $ticket_name,
        int $estimate,
        int $total_time,
        string $user_first_name,
        string $user_last_name,
        string $sprint_name,
        string $project_name,
        string $expected_estimate,
        int $expected_estimate_in_minutes,
        string $expected_total_time,
        int $expected_total_in_minutes
    ) {
        //Given
        $dto = new ActivitySummaryExportDto(
            $ticket_id,
            $ticket_title,
            $ticket_name,
            '',
            $estimate,
            $total_time,
            $user_first_name,
            $user_last_name,
            $sprint_name,
            $project_name
        );
        $export = new TimeTrackingActivitySummaryExport(collect([$dto]));

        //When
        $result = $export->collection();

        //Then
        $this->assertCount(1, $result);
        $this->assertSame([
            1,
            $project_name,
            $sprint_name,
            $ticket_title??'',
            $ticket_name,
            $expected_estimate,
            $expected_estimate_in_minutes,
            $expected_total_time,
            $expected_total_in_minutes,
            $user_first_name . ' ' . $user_last_name,
        ], $result->first());
    }

    /**
     * @test
     */
    public function headings_ShouldReturnExpectedHeadings(): void
    {
        //Given
        $export = new TimeTrackingActivitySummaryExport(collect([]));

        //When
        $result = $export->headings();

        //Then
        $this->assertSame([
            'Lp.',
            'Projekt',
            'Sprint',
            'Zadanie',
            'Nazwa zadania',
            'Estymacja',
            'Estymacja(w minutach)',
            'Czas',
            'Czas(w minutach)',
            'Użytkownik',
        ], $result);
    }
}
