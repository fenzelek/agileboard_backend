<?php

namespace App\Modules\Sale\Http\Requests;

use App\Http\Requests\Request;

class PaymentMethod extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'invoice_restrict' => ['boolean'],
        ];
    }
}
