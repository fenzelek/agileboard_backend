<?php

namespace App\Modules\SaleReport\Http\Requests;

use App\Http\Requests\Request;
use App\Models\Other\InvoiceTypeStatus;
use Illuminate\Validation\Rule;

class InvoicesRegistry extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'invoice_type_id' => [
                'integer',
                Rule::exists('invoice_types', 'id')->whereNot('slug', InvoiceTypeStatus::PROFORMA),
            ],
            'year' => ['integer', 'min:2016', 'max:2050'],
            'month' => ['integer', 'min:1', 'max:12'],
            'vat_rate_id' => [
                'integer',
                Rule::exists('vat_rates', 'id'),
            ],
        ];
        if ($this->input('month')) {
            $rules['year'] = array_merge($rules['year'], ['required']);
        }

        return $rules;
    }
}
