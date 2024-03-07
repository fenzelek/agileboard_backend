<?php

namespace App\Modules\Agile\Http\Requests;

use App\Http\Requests\Request;

class SprintClone extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'string|required',
            'activated' => 'boolean',
        ];
    }
}
