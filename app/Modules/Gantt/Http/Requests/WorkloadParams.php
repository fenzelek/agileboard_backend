<?php

namespace App\Modules\Gantt\Http\Requests;

use App\Http\Requests\Request;

class WorkloadParams extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'from' => ['required','date'],
        ];
    }
}
