<?php

namespace App\Modules\SaleInvoice\Http\Requests;

use App\Http\Requests\Request;
use App\Modules\SaleInvoice\Traits\ModulesRules;
use App\Modules\SaleInvoice\Traits\CreateUpdateInvoiceRules;
use App\Modules\SaleInvoice\Traits\IncomingInvoiceType;
use App\Modules\SaleInvoice\Traits\NoVatPayer;
use Illuminate\Validation\Rule;

class UpdateInvoice extends Request
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
            'contractor_id' => [
                Rule::exists('contractors', 'id')->where('company_id', $selected_company_id),
            ],
            'sale_date' => [
                'date',
            ],
            'issue_date' => [
                'date',
            ],
            'price_net' => [
                'numeric',
                'min:-9999999.99',
                'max:9999999.99',
            ],
            'price_gross' => [
                'numeric',
                'min:-9999999.99',
                'max:9999999.99',
            ],
            'vat_sum' => [
                'numeric',
                'min:-9999999.99',
                'max:9999999.99',
            ],
            'gross_counted' => [
                'boolean',
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'payment_term_days' => [
                'integer',
                'min:-180',
                'max:180',
            ],
            'payment_method_id' => [
                Rule::exists('payment_methods', 'id'),
            ],
            'items' => [
                'array',
            ],
            'items.*.company_service_id' => [
                'required',
                Rule::exists('company_services', 'id')->where('company_id', $selected_company_id),
            ],
            'items.*.service_unit_id' => [
                'required',
                Rule::exists('service_units', 'id'),
            ],
            'taxes' => [
                'array',
            ],
            'items.*.vat_rate_id' => ['required', $this->allowVatRates()],
            'taxes.*.vat_rate_id' => ['required', $this->allowVatRates()],
        ];

        $rules = $this->invoiceUpdateCommonRules($rules);
        $rules = $this->bankAccountRule($rules, $selected_company_id);

        if ($this->input('gross_counted')) {
            $rules['items.*.price_gross'] = ['required', 'numeric', 'min:0.01', 'max:9999999.99'];
        } else {
            $rules['items.*.price_net'] = ['required', 'numeric', 'min:0.01', 'max:9999999.99'];
        }

        $rules['id'] = [
            'invoice_is_editable',
            'allow_updating_invoice:' . $this->allowInvoiceTypes()->implode(','),
        ];

        if ($this->isAdvanceType()) {
            //block updating correction and subtype of correction
            $rules['payment_term_days'] = [Rule::in([0])];
        }

        $rules_including_application_settings = $this->requestRule($rules);

        $rules_including_no_vat_payers = $this->mergingNoVatPayersRules($rules_including_application_settings);

        return $rules_including_no_vat_payers;
    }

    /**
     * @inheritdoc
     */
    public function all($keys = null)
    {
        $data = parent::all();
        $data['id'] = $this->route('id');

        return $data;
    }
}
