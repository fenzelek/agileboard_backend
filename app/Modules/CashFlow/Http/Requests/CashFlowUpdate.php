<?php

namespace App\Modules\CashFlow\Http\Requests;

use App\Http\Requests\Request;

class CashFlowUpdate extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'cashless' => ['required', 'boolean'],
        ];
    }
}
