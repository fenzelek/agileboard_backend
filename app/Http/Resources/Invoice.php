<?php

namespace App\Http\Resources;

class Invoice extends AbstractResource
{
    protected $fields = [
        'id',
        'number',
        'order_number',
        'invoice_registry_id',
        'drawer_id',
        'company_id',
        'contractor_id',
        'delivery_address_id',
        'default_delivery',
        'corrected_invoice_id',
        'correction_type',
        'invoice_margin_procedure_id',
        'invoice_reverse_charge_id',
        'proforma_id',
        'sale_date',
        'issue_date',
        'invoice_type_id',
        'price_net',
        'price_gross',
        'vat_sum',
        'payment_left',
        'payment_term_days',
        'payment_method_id',
        'paid_at',
        'gross_counted',
        'description',
        'last_printed_at',
        'last_send_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $ignoredRelationships = [
        'parentInvoices',
        'nodeInvoices',
    ];

    public function toArray($request)
    {
        $data = parent::toArray($request);

        $data['created_at'] = $this->created_at->toDateTimeString();
        $data['updated_at'] = $this->updated_at->toDateTimeString();
        $data['paid_at'] = ($this->paid_at == null) ? null :
            $this->paid_at->toDateTimeString();
        $data['deleted_at'] = ($this->deleted_at == null) ? null :
            $this->deleted_at->toDateTimeString();
        $data['price_net'] = $this->undoNormalizePriceNet();
        $data['price_gross'] = $this->undoNormalizePriceGross();
        $data['vat_sum'] = $this->undoNormalizeVatSum();
        $data['payment_left'] = $this->undoNormalizePaymentLeft();

        if ($this->shouldAddInvoices($this)) {
            $related = collect();

            $this->invoices->each(function ($invoice) use ($related) {
                $related->push([
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                ]);
            });

            $data['invoices']['data'] = $related->all();
            $data['is_editable'] = $this->isEditable();
        }
        $data['invoice_contractor'] = [
            'data' => $this->invoiceContractor,
        ];

        return $data;
    }

    /**
     * Verify whether invoices attribute should be added into output.
     *
     * @param \App\Models\Db\Invoice $object
     *
     * @return bool
     */
    protected function shouldAddInvoices($object)
    {
        $loaded_relations = array_keys($object->getRelations());

        if (in_array('parentInvoices', $loaded_relations) &&
            in_array('nodeInvoices', $loaded_relations)
        ) {
            return true;
        }

        return false;
    }
}
