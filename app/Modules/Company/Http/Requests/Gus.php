<?php

namespace App\Modules\Company\Http\Requests;

use App\Http\Requests\Request;

class Gus extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'vatin' => ['required', 'max:15'],
        ];
    }
}
