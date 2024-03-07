<?php

namespace App\Modules\SaleInvoice\Http\Requests;

use App\Http\Requests\Request;
use App\Models\Db\PaymentMethod;
use App\Modules\SaleInvoice\Http\Requests\Rules\Correction;
use App\Modules\SaleInvoice\Traits\ModulesRules;
use App\Modules\SaleInvoice\Traits\CreateUpdateInvoiceRules;
use App\Modules\SaleInvoice\Traits\IncomingInvoiceType;
use App\Models\Other\InvoiceCorrectionType;
use App\Modules\SaleInvoice\Traits\NoVatPayer;
use Illuminate\Validation\Rule;
use App\Models\Other\InvoiceTypeStatus;
use App\Models\Db\InvoiceType;
use App\Models\Db\Invoice;

class CreateInvoice extends Request
{
    use ModulesRules, IncomingInvoiceType, CreateUpdateInvoiceRules, NoVatPayer;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $selected_company_id = auth()->user()->getSelectedCompanyId();
        $rules = [
            'extra_item_id' => [
                'sometimes',
                'array',
            ],
            'extra_item_id.*' => [
                'numeric',
                'distinct',
            ],
            'extra_item_type' => [
                'nullable',
                'in:receipts,online_sales',
            ],
            'issue_date' => [
                'required',
                'date',
            ],
            'sale_date' => ['required', 'date'],
            'invoice_type_id' => [
                'required',
                Rule::in($this->allowInvoiceTypes()->toArray()),
            ],
            'payment_method_id' => [
                'required',
                Rule::exists('payment_methods', 'id'),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'payment_term_days' => array_merge(['required', 'integer', 'min:-180', 'max:180'], $this->restrictPaymentTermDays()),
            'items' => ['required', 'array'],
            'items.*.company_service_id' => [
                'required',
                Rule::exists('company_services', 'id')->where('company_id', $selected_company_id),
            ],
            'taxes' => ['required', 'array'],
            'items.*.vat_rate_id' => ['required', $this->allowVatRates()],
            'taxes.*.vat_rate_id' => ['required', $this->allowVatRates()],
            'invoice_registry_id' => [
                'required',
                Rule::exists('invoice_registries', 'id')->where('company_id', $selected_company_id),
            ],
        ];

        $rules = $this->invoiceUpdateCommonRules($rules);
        $rules = $this->bankAccountRule($rules, $selected_company_id);

        // if extra_item_type is given this should be id of record in table
        if (in_array($this->input('extra_item_type'), ['receipts', 'online_sales'])) {
            $rules['extra_item_id.*'][] = Rule::exists($this->input('extra_item_type'), 'id')
                ->where('company_id', $selected_company_id);
        }

        if ($this->input('gross_counted')) {
            $rules['items.*.price_gross'] = ['required', 'numeric', 'min:0.01', 'max:9999999.99'];
        } else {
            $rules['items.*.price_net'] = ['required', 'numeric', 'min:0.01', 'max:9999999.99'];
        }

        // For collective invoice check if base_document_id in items matches id in extra_item_id
        $extra_item_id = $this->input('extra_item_id');
        if (is_array($extra_item_id) && count($extra_item_id) > 1) {
            $rules['items.*.base_document_id'] = [
                'required',
                'numeric',
                'in:' .
                implode(',', $this->input('extra_item_id')),
            ];
        }

        if ($this->isType(InvoiceTypeStatus::CORRECTION) || $this->isSubtypeOf(InvoiceTypeStatus::CORRECTION)
            || $this->isType(InvoiceTypeStatus::ADVANCE_CORRECTION)) {
            $specialization_rules = $this->specializationCorrectionRules($selected_company_id);
        } else {
            $specialization_rules = [
                'price_net' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
                'price_gross' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
                'vat_sum' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
                'contractor_id' => [
                    'required',
                    Rule::exists('contractors', 'id')->where('company_id', $selected_company_id),
                ],
                'gross_counted' => ['required', 'boolean'],
                'items.*.quantity' => ['required', 'numeric', 'min:0.001', 'max:99999.999'],
                'items.*.service_unit_id' => [
                    'required',
                    'integer',
                    Rule::exists('service_units', 'id'),
                ],
                'items.*.price_net_sum' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
                'items.*.price_gross_sum' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
                'items.*.vat_sum' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
                'taxes.*.price_net' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
                'taxes.*.price_gross' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            ];
        }

        $rules = array_merge($rules, $specialization_rules);

        $rules_including_application_settings = $this->requestRule($rules);

        $rules_including_no_vat_payers = $this->mergingNoVatPayersRules($rules_including_application_settings);

        return $rules_including_no_vat_payers;
    }

    /**
     * @param $selected_company_id
     * @return array
     */
    protected function specializationCorrectionRules($selected_company_id): array
    {
        $corrected_invoice = Invoice::findOrFail($this->input('corrected_invoice_id'));

        return  [
            'corrected_invoice_id' => [
                'required',
                Rule::exists('invoices', 'id')->where('company_id', $selected_company_id)
                    ->where('invoice_type_id', $this->canCorrectedType()),
            ],
            'invoice_registry_id' => [
                'required',
                Rule::in([$corrected_invoice->invoice_registry_id]),
            ],
            'price_net' => ['required', 'numeric', 'min:-9999999.99', 'max:9999999.99'],
            'price_gross' => ['required', 'numeric', 'min:-9999999.99', 'max:9999999.99'],
            'vat_sum' => ['required', 'numeric', 'min:-9999999.99', 'max:9999999.99'],
            'contractor_id' => [
                'required',
                Rule::in([$corrected_invoice->contractor_id]),
            ],
            'gross_counted' => [
                'required',
                'boolean',
                Rule::in([$corrected_invoice->gross_counted]),
            ],
            'correction_type' => [
                'required',
                Rule::in($this->allowCorrectionTypes()),
            ],
            'items.*.quantity' => ['required', 'numeric', 'min:-99999.999', 'max:99999.999'],
            'items.*.service_unit_id' => [
                'required',
                'integer',
                Rule::exists('service_units', 'id'),
            ],
            'items.*.position_corrected_id' => [
                'required',
                Rule::exists('invoice_items', 'id')
                    ->where('invoice_id', $corrected_invoice->id),
            ],
            'items.*.price_net_sum' => ['required', 'numeric', 'max:9999999.99', 'min:-9999999.99'],
            'items.*.price_gross_sum' => [
                'required',
                'numeric',
                'max:9999999.99',
                'min:-9999999.99',
            ],
            'items.*.vat_sum' => ['required', 'numeric', 'max:9999999.99', 'min:-9999999.99'],
            'taxes.*.price_net' => ['required', 'numeric', 'min:-9999999.99', 'max:9999999.99'],
            'taxes.*.price_gross' => ['required', 'numeric', 'min:-9999999.99', 'max:9999999.99'],
        ];
    }

    /**
     * Collect allowing correction type of invoice for given invoice type.
     *
     * @return array
     */
    protected function allowCorrectionTypes()
    {
        if ($this->isMarginType() || $this->isReverseChargeType()) {
            return [
                InvoiceCorrectionType::PRICE,
                InvoiceCorrectionType::QUANTITY,
            ];
        }

        return array_keys(InvoiceCorrectionType::all(auth()->user()->selectedCompany()));
    }

    /**
     * Get invoice type which can be corrected.
     * @return int
     */
    protected function canCorrectedType()
    {
        if ($this->isMarginType()) {
            return InvoiceType::findBySlug(InvoiceTypeStatus::MARGIN)->id;
        }

        if ($this->isReverseChargeType()) {
            return InvoiceType::findBySlug(InvoiceTypeStatus::REVERSE_CHARGE)->id;
        }
        if ($this->isAdvanceType()) {
            return InvoiceType::findBySlug(InvoiceTypeStatus::ADVANCE)->id;
        }

        return InvoiceType::findBySlug(InvoiceTypeStatus::VAT)->id;
    }

    /**
     * Collect restriction payment term days of invoice.
     *
     * @return array
     */
    protected function restrictPaymentTermDays()
    {
        if ($this->isType(InvoiceTypeStatus::PROFORMA)) {
            return [];
        }

        if ($this->isType(InvoiceTypeStatus::ADVANCE)) {
            return [Rule::in([0])];
        }

        // if it was paid in advance and sale date is same as issue date or it was correction
        // invoice, then payment_term_days should be zero
        if (PaymentMethod::paymentInAdvance($this->input('payment_method_id'))) {
            $issue_date = $this->input('issue_date');
            $sale_date = $this->input('sale_date');

            if (($issue_date && $sale_date && $issue_date == $sale_date)) {
                return [Rule::in([0])];
            }
        }

        return [];
    }
}
