<?php

declare(strict_types=1);

namespace App\Exports;

use App\Exports\Traits\Formatter;
use App\Modules\Integration\Models\ActivitySummaryExportDto;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class TimeTrackingActivitySummaryExport implements FromCollection, WithHeadings, WithStrictNullComparison
{
    use Exportable;
    use Formatter;

    private Collection $activities;

    public function __construct(
        Collection $activities
    ) {
        $this->activities = $activities;
    }

    public function collection(): Collection
    {
        $i = 0;

        return $this->activities->map(function (ActivitySummaryExportDto $dto) use (&$i) {
            $i++;

            return [
                $i,
                $dto->getProjectName(),
                $dto->getSprintName(),
                $dto->getTicketTitle() ?? '',
                $dto->getTicketName() ?? '',
                $this->formatSeconds($dto->getEstimate()),
                $this->getMinutesFromSeconds($dto->getEstimate()),
                $this->formatSeconds($dto->getTotalTime()),
                $this->getMinutesFromSeconds($dto->getTotalTime()),
                $dto->getUserFirstName() . ' ' . $dto->getUserLastName(),
            ];
        });
    }

    public function headings(): array
    {
        return [
            __('No.'),
            __('Project'),
            __('Sprint'),
            __('Ticket'),
            __('Ticket name'),
            __('Estimate'),
            __('Estimate(minutes)'),
            __('Time'),
            __('Time(minutes)'),
            __('User'),
        ];
    }
}
