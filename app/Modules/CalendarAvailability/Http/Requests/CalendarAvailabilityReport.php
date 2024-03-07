<?php

namespace App\Modules\CalendarAvailability\Http\Requests;

use App\Rules\UserIdsValidation;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class CalendarAvailabilityReport extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'in_year' => ['required', 'boolean'],
            'date' => ['required', 'date'],
            'users_ids' => [
                    'required',
                    'array',
                    new UserIdsValidation($this->user()),
                ],
        ];
    }

    public function getUsersIds(): ?array
    {
        return $this->input('users_ids');
    }

    public function getFrom(): Carbon
    {
        if ($this->inYear()) {
            return Carbon::parse($this->input('date'))->startOfYear();
        }

        return Carbon::parse($this->input('date'))->startOfMonth();
    }

    public function getTo(): Carbon
    {
        if ($this->inYear()) {
            return Carbon::parse($this->input('date'))->endOfYear();
        }

        return Carbon::parse($this->input('date'))->endOfMonth();
    }

    public function inYear(): bool
    {
        return (bool) $this->input('in_year');
    }
}
