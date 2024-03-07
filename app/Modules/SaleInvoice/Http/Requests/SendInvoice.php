<?php

namespace App\Modules\SaleInvoice\Http\Requests;

use App\Http\Requests\Request;
use App\Modules\SaleInvoice\Traits\CompanyResource;

class SendInvoice extends Request
{
    use CompanyResource;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'email' => [
                'required',
                'email',
            ],
        ];

        return $rules + $this->addCompanyResourceRule();
    }
}
