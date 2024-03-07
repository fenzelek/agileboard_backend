<?php

namespace App\Modules\CalendarAvailability\Http\Requests;

use App\Http\Requests\Request;
use App\Models\Other\SortDto;
use Illuminate\Validation\Rule;
use App\Models\Other\DepartmentType;

class CalendarAvailabilityIndex extends Request
{
    public function rules(): array
    {
        return [
            'from' => ['required', 'date'],
            'limit' => ['int', 'min:1', 'max:31'],
            'sorts' => ['array'],
            'sorts.*.field' => ['required', 'string', Rule::in(['last_name', 'department', 'contract_type'])],
            'sorts.*.direction' => ['required', 'string', Rule::in(['asc', 'desc'])],
            'department' => [Rule::in(DepartmentType::all())],
        ];
    }

    public function getSorts(): array
    {
        if (! is_array($this->input('sorts'))) {
            return [];
        }

        $sorts = [];
        foreach ($this->input('sorts') as $sort) {
            $sorts[] = new SortDto($sort['field'], $sort['direction']);
        }

        return $sorts;
    }

    public function getDepartment(): ?string
    {
        return $this->query('department');
    }
}
