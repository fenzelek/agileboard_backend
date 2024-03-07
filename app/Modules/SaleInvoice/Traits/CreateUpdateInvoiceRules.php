<?php

namespace App\Modules\SaleInvoice\Traits;

use App\Models\Db\PaymentMethod;
use App\Models\Other\InvoiceTypeStatus;
use App\Models\Other\PaymentMethodType;
use Illuminate\Validation\Rule;

trait CreateUpdateInvoiceRules
{
    /**
     * Add common request rule for creating and updating an invoice.
     *
     * @param $rules
     *
     * @return array
     */
    protected function invoiceUpdateCommonRules($rules)
    {
        if ($this->isType(InvoiceTypeStatus::ADVANCE)) {
            return $rules;
        }

        $price_gross = is_numeric($this->price_gross) ? $this->price_gross : 0;

        $rules += [
            'special_payment' => ['array'],
            'special_payment.amount' => [
                'required_with:special_payment',
                'numeric',
                'min:0.01',
                'max:9999999.99',
                'max:' . ($price_gross - 0.01),
            ],
            'special_payment.payment_method_id' => [
                'required_with:special_payment',
                'numeric',
                'different:payment_method_id',
                Rule::in([
                    PaymentMethod::findBySlug(PaymentMethodType::CASH)->id,
                    PaymentMethod::findBySlug(PaymentMethodType::DEBIT_CARD)->id,
                ]),
            ],
        ];

        // Quantity decimal check
        if (is_array($this->input('items'))) {
            foreach ($this->input('items') as $key => $item) {
                if (isset($item['service_unit_id'])) {
                    $rules['items.' . $key . '.quantity'] =
                        'decimal_quantity:' . $item['service_unit_id'];
                }
            }
        }

        return $rules;
    }

    protected function bankAccountRule($rules, $company_id)
    {
        $rules['bank_account_id'] = [
            'nullable',
            Rule::exists('bank_accounts', 'id')->where('company_id', $company_id),
        ];

        return $rules;
    }
}
