<?php

namespace App\Modules\User\Http\Requests;

use App\Http\Requests\Request;
use App\Rules\Blacklist;

class CreateUser extends Request
{
    public function rules()
    {
        $rules = [
            'email' => ['required', 'email', 'unique:users,email', new Blacklist()],
            'password' => ['required', 'confirmed', 'min:6'],
            'first_name' => ['required', 'max:255'],
            'last_name' => ['required', 'max:255'],
            'url' => ['required'],
            'language' => ['in:en,pl'],
            'discount_code' => ['max:255'],
        ];

        return $rules;
    }
}
