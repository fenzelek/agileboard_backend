<?php

namespace App\Modules\CalendarAvailability\Http\Requests;

use App\Http\Requests\Request;
use App\Models\Other\UserCompanyStatus;
use App\Modules\CalendarAvailability\Contracts\AvailabilityStore;
use App\Modules\CalendarAvailability\Http\Requests\Traits\CalendarAvailabilityStoreTrait;
use Illuminate\Validation\Rule;

class CalendarAvailabilityStore extends Request implements AvailabilityStore
{
    use CalendarAvailabilityStoreTrait;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = $this->validationRules();

        //check process user
        $rules['user'] = Rule::exists('user_company', 'user_id')
            ->where('company_id', $this->input('selected_company_id'))
            ->where('user_id', $this->route('user')->id)
            ->where('status', UserCompanyStatus::APPROVED);

        $rules['availabilities.*.status'] = 'in:ADDED,CONFIRMED';

        return $rules;
    }

    public function all($keys = null)
    {
        $data = parent::all();
        // add extra data that should be validated
        $data['day'] = $this->route('day');
        $data['user'] = $this->route('user')->id;

        return $data;
    }
}
