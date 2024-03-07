<?php

namespace App\Modules\SaleInvoice\Http\Requests;

use App\Http\Requests\Request;

class CreateInvoicePdf extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'duplicate' => ['boolean'],
        ];
    }
}
