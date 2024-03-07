<?php

namespace App\Http\Resources;

class InvoiceItem extends AbstractResource
{
    protected $fields = [
        'id',
        'invoice_id',
        'company_service_id',
        'pkwiu',
        'name',
        'type',
        'custom_name',
        'price_net',
        'price_net_sum',
        'price_gross',
        'price_gross_sum',
        'vat_rate',
        'vat_rate_id',
        'vat_sum',
        'quantity',
        'service_unit_id',
        'base_document_id',
        'is_correction',
        'position_corrected_id',
        'proforma_item_id',
        'paid',
        'creator_id',
        'editor_id',
        'created_at',
        'updated_at',
    ];

    public function toArray($request)
    {
        $data = parent::toArray($request);

        $data['created_at'] = $this->created_at ? $this->created_at->toDateTimeString() : null;
        $data['updated_at'] = $this->updated_at ? $this->updated_at->toDateTimeString() : null;
        $data['price_net'] = $this->undoNormalizePriceNet();
        $data['price_net_sum'] = $this->undoNormalizePriceNetSum();
        $data['price_gross'] = $this->undoNormalizePriceGross();
        $data['price_gross_sum'] = $this->undoNormalizePriceGrossSum();
        $data['quantity'] = denormalize_quantity($this->quantity);
        $data['paid'] = $this->paid->render();
        $data['vat_sum'] = $this->undoNormalizeVatSum();

        return $data;
    }
}
