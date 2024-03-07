<?php

declare(strict_types=1);

namespace App\Modules\CalendarAvailability\Http\Requests;

use App\Http\Requests\Request;
use App\Models\Other\DepartmentType;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class CalendarAvailabilityExportRequest extends Request
{
    public function rules(): array
    {
        return [
            'from' => ['required', 'date'],
            'limit' => ['int', 'min:1', 'max:31'],
            'department' => ['string', Rule::in(DepartmentType::all())],
        ];
    }

    public function getStartDate(): Carbon
    {
        return Carbon::parse($this->input('from'))->startOfWeek();
    }

    public function getEndDate(): Carbon
    {
        return $this->getStartDate()
            ->addDays($this->input('limit', 10) - 1);
    }

    public function getDepartment(): ?string
    {
        return $this->input('department');
    }
}
