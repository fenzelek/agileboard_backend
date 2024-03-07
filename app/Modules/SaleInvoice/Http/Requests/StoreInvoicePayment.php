<?php

namespace App\Modules\SaleInvoice\Http\Requests;

use App\Http\Requests\Request;
use App\Models\Db\Invoice;
use App\Models\Db\InvoiceType;
use App\Models\Other\InvoiceTypeStatus;
use Illuminate\Validation\Rule;

class StoreInvoicePayment extends Request
{
    /**
     * @var InvoiceType
     */
    protected $invoice_type;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $this->invoice_type = app()->make(InvoiceType::class);
        $invoice_type = $this->invoice_type::findBySlug(InvoiceTypeStatus::PROFORMA);

        $rules = [
            'invoice_id' => [
                'required',
                'numeric',
                Rule::exists('invoices', 'id')
                    ->where('company_id', auth()->user()->getSelectedCompanyId())
                    ->whereNot('invoice_type_id', $invoice_type->id),
            ],
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                'max:999999999',
            ],
            'payment_method_id' => [
                'required',
                'numeric',
                'exists:payment_methods,id',
            ],
        ];

        // if invoice is valid we want to make sure amount isn't grater then invoice price gross
        if ($this->input('invoice_id')) {
            $invoice = Invoice::where('company_id', auth()->user()->getSelectedCompanyId())
                ->find($this->input('invoice_id'));
            if ($invoice) {
                $rules['amount'][] = 'max:' . denormalize_price($invoice->price_gross);
            }
        }

        return $rules;
    }
}
