<?php

namespace App\Modules\SaleInvoice\Http\Requests;

use App\Http\Requests\Request;
use App\Models\Other\SaleInvoice\FilterOption;
use Illuminate\Validation\Rule;

class CompanyInvoices extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $company_id = auth()->user()->getSelectedCompanyId();

        return [
            'id' => [
                'numeric',
                Rule::exists('invoices', 'id')->where('company_id', $company_id),
            ],
            'contractor_id' => [
                'numeric',
                Rule::exists('contractors', 'id')->where('company_id', $company_id),
            ],
            'drawer_id' => [
                'numeric',
                Rule::exists('users', 'id'),
            ],
            'status' => [Rule::in(FilterOption::all()),
            ],
            'date_start' => [
                'date_format:"Y-m-d"',
            ],
            'date_end' => [
                'date_format:"Y-m-d"',
            ],
            'proforma_id' => [
                'integer',
                Rule::exists('invoices', 'proforma_id'),
            ],
            'invoice_type_id' => [
                'integer',
                Rule::exists('invoice_types', 'id'),
            ],
            'invoice_registry_id' => [
                'integer',
                Rule::exists('invoice_registries', 'id')->where('company_id', $company_id),
            ],
        ];
    }
}
