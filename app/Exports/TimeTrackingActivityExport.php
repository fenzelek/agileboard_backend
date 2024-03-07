<?php

declare(strict_types=1);

namespace App\Exports;

use App\Exports\Traits\Formatter;
use App\Modules\Integration\Models\ActivityExportDto;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class TimeTrackingActivityExport implements FromCollection, WithHeadings, WithColumnFormatting, WithStrictNullComparison
{
    use Exportable;
    use Formatter;

    private Collection $activities;

    private int $utc_offset;

    public function __construct(
        Collection $activities,
        int $utc_offset
    ) {
        $this->activities = $activities;
        $this->utc_offset = $utc_offset;
    }

    public function collection(): Collection
    {
        $i=0;

        return $this->activities->map(function (ActivityExportDto $dto) use (&$i) {
            $i++;
            $started_at = $dto->getUtcStartedAt() ?
                $this->formatUtcDateForExcel($dto->getUtcStartedAt(), $this->utc_offset) :
                '';
            $finished_at = $dto->getUtcFinishedAt() ?
                $this->formatUtcDateForExcel($dto->getUtcFinishedAt(), $this->utc_offset) :
                '';

            return [
                $i,
                $started_at,
                $finished_at,
                $this->formatSeconds($dto->getTracked()),
                $this->getMinutesFromSeconds($dto->getTracked()),
                $dto->getUserFirstName() . ' ' . $dto->getUserLastName(),
                $dto->getProjectName(),
                $dto->getSprintName(),
                $dto->getTicketTitle(),
            ];
        });
    }

    public function headings(): array
    {
        return [
            __('No.'),
            __('Start date'),
            __('Finish date'),
            __('Time'),
            __('Time(minutes)'),
            __('User'),
            __('Project'),
            __('Sprint'),
            __('Ticket'),
        ];
    }

    public function columnFormats(): array
    {
        return [
            'B' => 'yyyy-mm-dd hh:mm:ss',
            'C' => 'yyyy-mm-dd hh:mm:ss',
        ];
    }
}
