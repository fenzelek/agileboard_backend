<?php

namespace App\Modules\SaleOther\Http\Requests;

use App\Http\Requests\Request;
use App\Models\Other\PaymentMethodType;
use Illuminate\Validation\Rule;

class CreateReceipt extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $selected_company_id = auth()->user()->getSelectedCompanyId();

        $rules = [
            'transaction_number' => ['required', 'max:63'],
            'sale_date' => ['required','date'],
            'price_net' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'price_gross' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'vat_sum' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'payment_method' => [
                'required',
                Rule::exists('payment_methods', 'slug'),
            ],
            'number' => ['required', 'max:63'],
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
            'payment_method_types' => ['required', 'array'],
            'payment_method_types.*.amount' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],

        ];

        if ($this->input('payment_method') == PaymentMethodType::CASH_CARD) {
            $rules['payment_method_types.0.type'] = [
                'required',
                'different:payment_method_types.1.type',
                Rule::in([
                    PaymentMethodType::CASH,
                    PaymentMethodType::DEBIT_CARD,
                ]),
            ];
            $rules['payment_method_types.1.type'] = [
                'required',
                'different:payment_method_types.0.type',
                Rule::in([
                    PaymentMethodType::CASH,
                    PaymentMethodType::DEBIT_CARD,
                ]),
            ];
        } else {
            $rules['payment_method_types.*.type'] = [
                'required',
                Rule::in([
                    PaymentMethodType::CASH,
                    PaymentMethodType::DEBIT_CARD,
                ]),
            ];
        }

        return $rules;
    }
}
