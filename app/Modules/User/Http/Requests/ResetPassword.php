<?php

namespace App\Modules\User\Http\Requests;

use App\Http\Requests\Request;

class ResetPassword extends Request
{
    public function rules()
    {
        return [
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:6'],
        ];
    }
}
