<?php

namespace App\Modules\Company\Http\Requests;

use App\Http\Requests\Request;

class CompanySettingsUpdate extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'force_calendar_to_complete' => ['required', 'boolean'],
            'enable_calendar' => ['required', 'boolean'],
            'enable_activity' => ['required', 'boolean'],
        ];
    }
}
