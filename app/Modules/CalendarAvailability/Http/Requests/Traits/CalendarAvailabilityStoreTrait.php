<?php

namespace App\Modules\CalendarAvailability\Http\Requests\Traits;

use App\Models\Other\UserCompanyStatus;
use App\Modules\CalendarAvailability\Http\Requests\UserAvailability;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

trait CalendarAvailabilityStoreTrait
{
    /**
     * @return array
     */
    private function validationRules(): array
    {
        $rules = [
            'availabilities' => ['array'],
            'availabilities.*.time_start' => ['date_format:H:i:s'],
            'availabilities.*.time_stop' => ['date_format:H:i:s'],
            'availabilities.*.available' => ['required', 'boolean'],
            'availabilities.*.overtime' => ['boolean'],
            'availabilities.*.description' => ['max:50'],
            'selected_company_id' => [
                'required',
                Rule::exists('user_company', 'company_id')
                    ->where('user_id', auth()->user()->id)
                    ->where('status', UserCompanyStatus::APPROVED),
            ],
        ];
        $rules['day'] = ['required', 'date'];

        return $rules;
    }

    public function getCompanyId(): int
    {
        return $this->input('selected_company_id');
    }

    public function getDay()
    {
        return $this->route('day');
    }

    /**
     * @return UserAvailability[]
     */
    public function getAvailabilities(): array
    {
        return Collection::make($this->input('availabilities'))->map(function (array $raw_user_availability) {
            return new UserAvailability($raw_user_availability);
        })->all();
    }
}
