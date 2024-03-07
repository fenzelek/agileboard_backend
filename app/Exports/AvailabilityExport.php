<?php

declare(strict_types=1);

namespace App\Exports;

use App\Modules\CalendarAvailability\Models\AvailabilityExportDto;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class AvailabilityExport implements FromCollection, WithHeadings, WithColumnFormatting
{
    private Collection $availabilities;

    public function __construct(Collection $availabilities)
    {
        $this->availabilities = $availabilities;
    }

    public function collection(): Collection
    {
        $i=0;

        return $this->availabilities->map(function (AvailabilityExportDto $dto) use (&$i) {
            $i++;

            return [
                $i,
                $dto->getUserFirstName() . ' ' . $dto->getUserLastName(),
                __($dto->getDepartment()??''),
                $dto->getDay() !==null ? Date::dateTimeToExcel($dto->getDay()) : '',
                $this->getType($dto),
                $dto->getDescription(),
                $dto->getTimeStart()??'',
                $dto->getTimeStop()??'',
            ];
        });
    }

    public function headings(): array
    {
        return [
            __('No.'),
            __('User'),
            __('Department'),
            __('Day'),
            __('Type'),
            __('Description'),
            __('Time start'),
            __('Time stop'),
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_DATE_YYYYMMDD,
        ];
    }

    private function getType(AvailabilityExportDto $dto): string
    {
        if ($dto->getIsUserAvailable() === null || $dto->getIsOvertime() === null) {
            return '';
        }
        if (! $dto->getIsUserAvailable()) {
            return __('Day off');
        }
        if ($dto->getIsOvertime()) {
            return __('Overtime');
        }

        return __('Working hours');
    }
}
