<?php

declare(strict_types=1);

namespace Tests\Unit\App\Exports\SprintExport;

use App\Exports\SprintExport;
use App\Modules\Agile\Models\TicketExportDto;
use Tests\TestCase;

class SprintExportTest extends TestCase
{
    use SprintExportTrait;

    /**
     * @test
     * @dataProvider fromCollectionProvider
     */
    public function fromCollection_ShouldReturnCorrectlyFormattedTicketData(
        int $ticket_id,
        string $user_first_name,
        string $user_last_name,
        string $ticket_title,
        string $ticket_name,
        int $ticket_estimated_seconds,
        int $ticket_tracked_seconds,
        int $expected_ordinal_number,
        string $expected_estimate_time,
        int $expected_estimate_time_in_minutes,
        string $expected_tracked_time,
        int $expected_tracked_time_in_minutes
    ) {
        //Given
        $dto = new TicketExportDto(
            $ticket_id,
            $ticket_name,
            $ticket_title,
            $user_first_name,
            $user_last_name,
            $ticket_estimated_seconds,
            $ticket_tracked_seconds
        );
        $expected_user_name = $user_first_name . ' ' . $user_last_name;
        $export = new SprintExport(collect([$dto]), 'Sprint name');

        //When
        $result = $export->collection();

        //Then
        /** @var array $data */
        $data = $result->first();
        $this->assertSame($expected_ordinal_number, $data[0]);
        $this->assertSame($ticket_title, $data[1]);
        $this->assertSame($ticket_name, $data[2]);
        $this->assertSame($expected_user_name, $data[3]);
        $this->assertSame($expected_estimate_time, $data[4]);
        $this->assertSame($expected_estimate_time_in_minutes, $data[5]);
        $this->assertSame($expected_tracked_time, $data[6]);
        $this->assertSame($expected_tracked_time_in_minutes, $data[7]);
    }
}
