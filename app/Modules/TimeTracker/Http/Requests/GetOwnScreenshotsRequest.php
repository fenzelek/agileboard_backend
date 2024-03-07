<?php

namespace App\Modules\TimeTracker\Http\Requests;

use App\Http\Requests\Request;
use App\Modules\TimeTracker\Http\Requests\Contracts\GetOwnScreenshotsRequestData;
use Illuminate\Validation\Rule;

class GetOwnScreenshotsRequest extends Request implements GetOwnScreenshotsRequestData
{
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'project_id' => [Rule::exists('projects','id')]
        ];
    }

    public function getDate(): string
    {
        return $this->input('date');
    }

    public function getSelectedCompanyId(): int
    {
        return $this->input('selected_company_id');
    }

    public function getProjectId(): ?int
    {
        return $this->input('project_id');
    }
}
