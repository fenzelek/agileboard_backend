<?php

declare(strict_types=1);

namespace App\Exports;

use App\Exports\Traits\Formatter;
use App\Modules\Agile\Models\TicketExportDto;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SprintExport implements FromCollection, WithHeadings
{
    use Formatter;

    private Collection $tickets;

    private string $sprint_name;

    public function __construct(Collection $tickets, string $sprint_name)
    {
        $this->tickets = $tickets;
        $this->sprint_name = $sprint_name;
    }

    public function getTickets(): Collection
    {
        return $this->tickets;
    }

    public function getFileName(): string
    {
        return $this->sprint_name . '_' . Carbon::now()->timestamp;
    }

    public function collection(): Collection
    {
        $i=0;

        return $this->tickets->map(function (TicketExportDto $dto) use (&$i) {
            $i++;

            return $this->formatRow($dto, $i);
        });
    }

    private function formatRow(TicketExportDto $dto, int $index): array
    {
        return [
            $index,
            $dto->getTitle(),
            $dto->getName(),
            $dto->getUserFirstName() . ' ' . $dto->getUserLastName(),
            $this->formatSeconds($dto->getEstimatedSeconds()),
            $this->getMinutesFromSeconds($dto->getEstimatedSeconds()),
            $this->formatSeconds($dto->getTrackedSeconds()),
            $this->getMinutesFromSeconds($dto->getTrackedSeconds()),
        ];
    }

    public function headings(): array
    {
        return [
            __('No.'),
            __('Title'),
            __('Name'),
            __('User'),
            __('Estimate'),
            __('Estimate(minutes)'),
            __('Tracked'),
            __('Tracked(minutes)'),
        ];
    }
}
