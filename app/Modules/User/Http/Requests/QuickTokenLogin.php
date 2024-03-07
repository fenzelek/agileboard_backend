<?php

namespace App\Modules\User\Http\Requests;

use App\Http\Requests\Request;

class QuickTokenLogin extends Request
{
    public function rules()
    {
        return [
            'token' => ['required'],
        ];
    }
}
