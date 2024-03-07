<?php

declare(strict_types=1);

namespace Tests\Unit\App\Exports\TimeTrackingActivityExport;

use App\Exports\TimeTrackingActivityExport;
use App\Models\Db\Ticket;
use App\Modules\Integration\Models\ActivityExportDto;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TimeTrackingActivityExportTest extends TestCase
{
    use DatabaseTransactions;
    use TimeTrackingActivityExportTrait;

    /**
     * @test
     * @dataProvider collectionDataProvider
     */
    public function collection_ReturnCollectionInCorrectFormat(
        string $user_first_name,
        string $user_last_name,
        string $project_name,
        string $sprint_name,
        ?string $ticket_title,
        string $comment,
        int $tracked_time,
        ?string $utc_started_at,
        ?string $utc_finished_at,
        int $utc_offset,
        float $started_at_in_timezone,
        float $finished_at_in_timezone,
        string $expected_tracked_time,
        int $expected_tracked_time_in_minutes
    ): void {
        //Given
        $id = 1;
        $dto = new ActivityExportDto(
            $id,
            $user_first_name,
            $user_last_name,
            $utc_started_at ? Carbon::parse($utc_started_at) : null,
            $utc_finished_at ? Carbon::parse($utc_finished_at) : null,
            $tracked_time,
            $project_name,
            $sprint_name,
            $ticket_title,
            $comment
        );

        $export = new TimeTrackingActivityExport(collect([$dto]), $utc_offset);

        //When
        $result = $export->collection();

        //Then
        $this->assertSame([
            1,
            $started_at_in_timezone,
            $finished_at_in_timezone,
            $expected_tracked_time,
            $expected_tracked_time_in_minutes,
            "{$user_first_name} {$user_last_name}",
            $project_name,
            $sprint_name,
            $ticket_title,
        ], $result->first());
    }

    /**
     * @test
     */
    public function headings_ShouldReturnExpectedHeadings()
    {
        //Given
        $export = new TimeTrackingActivityExport(collect([]), 0);

        //When
        $result = $export->headings();

        $this->assertSame([
            'Lp.',
            'Data rozpoczęcia',
            'Data zakończenia',
            'Czas',
            'Czas(w minutach)',
            'Użytkownik',
            'Projekt',
            'Sprint',
            'Zadanie',
        ], $result);
    }

    protected function setUp(): void
    {
        parent::setUp();
        config('app.locale', 'pl');
        Ticket::unsetEventDispatcher();
    }
}
