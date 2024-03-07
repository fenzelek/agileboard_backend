<?php

namespace App\Modules\User\Http\Requests;

use App\Http\Requests\Request;

class AuthLogin extends Request
{
    public function rules()
    {
        return [
            'email' => ['required','email'],
            'password' => ['required'],
        ];
    }
}
