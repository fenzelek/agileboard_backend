<?php

namespace App\Modules\SaleInvoice\Traits;

use App\Models\Other\InvoiceTypeStatus;
use App\Models\Other\SaleInvoice\Payers\NoVat;
use Illuminate\Validation\Rule;

trait NoVatPayer
{
    /**
     * No vat payer limitation.
     *
     * @return array
     */
    protected function limits()
    {
        if (auth()->user()->getSelectedCompanyId() && auth()->user()->selectedCompany()->isVatPayer()) {
            return [];
        }

        $rules = [
            'gross_counted' => [Rule::in(NoVat::COUNT_TYPE)],
            'vat_sum' => [Rule::in(NoVat::vatAmount())],
            'items.*.vat_sum' => [Rule::in(NoVat::vatAmount())],
            'price_net' => ['same:price_gross'],
            'items.*.price_net' => ['same:items.*.price_gross'],
            'items.*.price_net_sum' => ['same:items.*.price_gross_sum'],
            'taxes.*.price_net' => ['same:taxes.*.price_gross'],
        ];

        if ($this->canIssuingAdvance() && $this->isType(InvoiceTypeStatus::FINAL_ADVANCE)) {
            $rules = array_merge($rules, [
                'advance_taxes.*.price_net' => ['same:advance_taxes.*.price_gross'],
            ]);
        }

        return $rules;
    }

    /**
     * Add no vat payer rules.
     *
     * @param array $rules
     * @return array
     */
    protected function mergingNoVatPayersRules(array $rules) : array
    {
        collect($this->limits())->each(function ($rule, $field) use (&$rules) {
            $rules[$field] = array_merge(array_get($rules, $field, []), $rule);
        });

        return $rules;
    }
}
