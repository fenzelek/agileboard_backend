<?php

namespace App\Modules\SaleOther\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

class CreateOnlineSale extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $selected_company_id = auth()->user()->getSelectedCompanyId();

        return [
            'email' => ['required', 'email', 'max:50'],
            'number' => ['required', 'max:63'],
            'transaction_number' => ['required', 'max:63'],
            'sale_date' => ['required','date'],
            'price_net' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'price_gross' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'vat_sum' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'items' => ['required', 'array'],
            'items.*.name' => ['required', 'max:255'],
            'items.*.price_net' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'items.*.price_net_sum' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'items.*.price_gross' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'items.*.price_gross_sum' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'items.*.vat_rate' => [
                'required', 'max:63',
                Rule::exists('vat_rates', 'name'),
            ],
            'items.*.vat_sum' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'items.*.quantity' => ['required', 'integer'],
        ];
    }
}
