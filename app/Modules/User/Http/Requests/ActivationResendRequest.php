<?php

namespace App\Modules\User\Http\Requests;

use App\Http\Requests\Request;

class ActivationResendRequest extends Request
{
    /**
     * Get validation rules.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => ['required','email'],
            'url' => 'required',
            'language' => ['in:en,pl'],
        ];
    }
}
