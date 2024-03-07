<?php

namespace App\Modules\SaleInvoice\Traits;

use App\Models\Db\Invoice;
use App\Models\Db\InvoiceType;
use App\Models\Db\VatRate;
use App\Models\Other\InvoiceTypeStatus;
use App\Models\Other\VatRateType;
use Illuminate\Validation\Rule;

trait IncomingInvoiceType
{
    /**
     * Check if incoming invoice type is margin billing.
     *
     * @return bool
     */
    public function isMarginType()
    {
        return $this->isType(InvoiceTypeStatus::MARGIN)
            || $this->isType(InvoiceTypeStatus::MARGIN_CORRECTION);
    }

    /**
     * Check if incoming invoice type is reverse charge billing.
     *
     * @return bool
     */
    public function isReverseChargeType()
    {
        return $this->isType(InvoiceTypeStatus::REVERSE_CHARGE)
            || $this->isType(InvoiceTypeStatus::REVERSE_CHARGE_CORRECTION);
    }

    /**
     * Check if incoming invoice type is advance billing.
     *
     * @return bool
     */
    public function isAdvanceType()
    {
        return $this->isType(InvoiceTypeStatus::ADVANCE)
            || $this->isType(InvoiceTypeStatus::ADVANCE_CORRECTION);
    }

    /**
     * Add margin type validation rules.
     *
     * @param $rules
     *
     * @return array
     */
    protected function marginTypeRules($rules)
    {
        if ($this->isMarginType()) {
            $rules['invoice_margin_procedure_id'] = [
                'required',
                Rule::exists('invoice_margin_procedures', 'id'),
            ];
            $rules['vat_sum'][] = Rule::in([0]);
            $rules['items.*.vat_sum'][] = Rule::in([0]);
        }

        return $rules;
    }

    /**
     * Add Reverse Charge type validation rules.
     *
     * @param $rules
     *
     * @return array
     */
    protected function reverseChargeTypeRules($rules)
    {
        if ($this->isReverseChargeType()) {
            $rules['invoice_reverse_charge_id'] = [
                'required',
                Rule::exists('invoice_reverse_charges', 'id'),
            ];
            $rules['vat_sum'][] = Rule::in([0]);
            $rules['items.*.vat_sum'][] = Rule::in([0]);
        }

        return $rules;
    }

    /**
     * Add Advance type validation rules.
     *
     * @param $rules
     *
     * @return array
     */
    protected function advanceTypeRules($rules)
    {
        if ($this->isType(InvoiceTypeStatus::ADVANCE) || $this->isSubtypeOf(InvoiceTypeStatus::ADVANCE)) {
            if (! $this->route('id')) {
                $invoice_type_model = app()->make(InvoiceType::class);
                $proforma_type = $invoice_type_model::findBySlug(InvoiceTypeStatus::PROFORMA);
                $rules['proforma_id'] = [
                    'required',
                    Rule::exists('invoices', 'id')->where('invoice_type_id', $proforma_type->id),
                ];
            }
            if ($proforma_id = $this->findProformaIdForAdvanceInvoice()) {
                $rules['items.*.proforma_item_id'] = [
                    'required',
                    Rule::exists('invoice_items', 'id')->where('invoice_id', $proforma_id),
                ];
            }
        }
        if ($this->isType(InvoiceTypeStatus::FINAL_ADVANCE)) {
            $rules = array_merge($rules, [
                'advance_taxes' => ['required', 'array'],
                'advance_taxes.*.vat_rate_id' => ['required', $this->allowVatRates()],
                'advance_taxes.*.price_net' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
                'advance_taxes.*.price_gross' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            ]);
        }

        return $rules;
    }

    /**
     * Find proforma id for issuing/updating Advance Invoice.
     */
    protected function findProformaIdForAdvanceInvoice()
    {
        if ($this->exists('proforma_id')) {
            return $this->input('proforma_id');
        }
        if ($this->route('id')) {
            $invoice_model = app()->make(Invoice::class);
            $invoice = $invoice_model::find($this->route('id'));
            if ($invoice) {
                return $invoice->proforma_id;
            }
        }

        return;
    }

    /**
     * Check if incoming invoice_type_id is type given by slug.
     *
     * @param $slug
     *
     * @return bool
     */
    protected function isType($slug)
    {
        $invoice_type = $this->getType();

        return empty($invoice_type) ? false : $invoice_type->isType($slug);
    }

    /**
     * Check if incoming invoice type is subtype given by slug.
     *
     * @param $slug
     *
     * @return bool
     */
    protected function isSubtypeOf($slug)
    {
        $invoice_type = $this->getType();

        return empty($invoice_type) ? false : $invoice_type->isSubtypeOf($slug);
    }

    /**
     * Get incoming invoice type.
     *
     * @return InvoiceType|null
     */
    protected function getType()
    {
        if ($this->route('id')) {
            $invoice_model = app()->make(Invoice::class);
            $invoice = $invoice_model::find($this->route('id'));
            if ($invoice) {
                return $invoice->invoiceType;
            }
        }
        if ($this->input('invoice_type_id')) {
            $invoice_type_model = app()->make(InvoiceType::class);

            return $invoice_type_model::find($this->input('invoice_type_id'));
        }

        return null;
    }

    /**
     * Collect allowing vat rates for given invoice type.
     *
     * @return \Illuminate\Validation\Rules\In
     */
    protected function allowVatRates()
    {
        if ($this->isMarginType() || $this->isReverseChargeType()) {
            return Rule::in(VatRate::where('name', VatRateType::NP)->pluck('id')->toArray());
        }

        return Rule::in(VatRate::pluck('id')->toArray());
    }
}
