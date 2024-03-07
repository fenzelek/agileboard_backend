<?php

namespace App\Modules\User\Http\Requests;

use App\Http\Requests\Request;

class SendResetEmail extends Request
{
    public function rules()
    {
        return [
            'email' => ['required', 'email'],
            'url' => ['required'],
            'language' => ['in:en,pl'],
        ];
    }
}
