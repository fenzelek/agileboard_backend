<?php

namespace App\Modules\CashFlow\Http\Requests;

use App\Http\Requests\Request;

class CashFlow extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'cashless' => ['required', 'boolean'],
        ];
    }
}
