<?php

namespace App\Modules\Agile\Http\Requests;

use App\Http\Requests\Request;

class ReportDaily extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'project_id' => ['nullable', 'integer'],
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d'],
        ];
    }
}
