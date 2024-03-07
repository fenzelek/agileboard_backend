<?php

namespace App\Modules\Agile\Http\Requests;

use App\Http\Requests\Request;

class StatusStore extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'statuses' => [
                'required',
                'array',
            ],
            'statuses.*.name' => ['required', 'max:255'],
        ];
    }
}
