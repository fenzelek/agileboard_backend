<?php

namespace App\Modules\Company\Http\Requests;

use App\Http\Requests\Request;

class RejectInvitation extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'token' => ['required'],
        ];
    }
}
