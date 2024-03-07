<?php

namespace App\Http\Resources;

class ReceiptItem extends AbstractResource
{
    protected $fields = [

        'id',
        'receipt_id',
        'company_service_id',
        'name',
        'price_net',
        'price_net_sum',
        'price_gross',
        'price_gross_sum',
        'vat_rate',
        'vat_rate_id',
        'vat_sum',
        'quantity',
        'creator_id',
        'editor_id',
        'created_at',
        'updated_at',
    ];

    public function toArray($request)
    {
        $data = parent::toArray($request);

        $data['created_at'] = $this->created_at->toDateTimeString();
        $data['updated_at'] = $this->updated_at->toDateTimeString();
        $data['price_net'] = $this->undoNormalizePriceNet();
        $data['price_net_sum'] = $this->undoNormalizePriceNetSum();
        $data['price_gross'] = $this->undoNormalizePriceGross();
        $data['price_gross_sum'] = $this->undoNormalizePriceGrossSum();
        $data['vat_sum'] = $this->undoNormalizeVatSum();

        return $data;
    }
}
