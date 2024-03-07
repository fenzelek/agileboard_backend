<?php

namespace App\Modules\CalendarAvailability\Http\Requests;

use App\Http\Requests\Request;

class CalendarAvailabilityShow extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'user' => ['required'],
            'day' => ['required', 'date'],
        ];

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
        $data['user'] = ($user = $this->route('user')) ? $user->id : null;

        return $data;
    }
}
