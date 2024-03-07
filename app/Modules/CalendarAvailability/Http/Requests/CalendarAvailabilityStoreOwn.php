<?php

namespace App\Modules\CalendarAvailability\Http\Requests;

use App\Http\Requests\Request;
use App\Modules\CalendarAvailability\Contracts\AvailabilityStore;
use App\Modules\CalendarAvailability\Http\Requests\Traits\CalendarAvailabilityStoreTrait;

class CalendarAvailabilityStoreOwn extends Request implements AvailabilityStore
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

        $rules['user'][] = 'in:' . auth()->user()->id;
        $rules['day'] = ['after:yesterday'];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function all($keys = null)
    {
        $data = parent::all();
        // add extra data that should be validated
        $data['day'] = $this->route('day');

        return $data;
    }
}
