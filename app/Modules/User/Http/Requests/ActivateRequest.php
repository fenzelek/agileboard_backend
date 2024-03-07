<?php

namespace App\Modules\User\Http\Requests;

use App\Http\Requests\Request;

class ActivateRequest extends Request
{
    /**
     * Get validation rules.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'activation_token' => 'required',
        ];
    }
}
