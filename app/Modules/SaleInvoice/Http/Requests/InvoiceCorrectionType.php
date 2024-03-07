<?php

namespace App\Modules\SaleInvoice\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

class InvoiceCorrectionType extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'invoice_id' => [
                'numeric',
                Rule::exists('invoices', 'id')->where('company_id', auth()->user()->getSelectedCompanyId()),
            ],
        ];
    }
}
