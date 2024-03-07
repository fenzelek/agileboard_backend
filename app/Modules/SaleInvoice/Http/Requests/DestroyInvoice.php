<?php

namespace App\Modules\SaleInvoice\Http\Requests;

use App\Http\Requests\Request;
use App\Modules\SaleInvoice\Traits\CompanyResource;
use App\Modules\SaleInvoice\Traits\ModulesRules;

class DestroyInvoice extends Request
{
    use CompanyResource;
    use ModulesRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return $this->addCompanyResourceRule();
    }
}
