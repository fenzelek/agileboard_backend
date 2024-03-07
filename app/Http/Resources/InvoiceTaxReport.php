<?php

namespace App\Http\Resources;

class InvoiceTaxReport extends AbstractResource
{
    protected $fields = [
        'id',
        'invoice_id',
        'vat_rate_id',
        'price_net',
        'price_gross',
        'created_at',
        'updated_at',
    ];

    public function toArray($request)
    {
        $data = parent::toArray($request);

        $data['created_at'] = $this->created_at->toDateTimeString();
        $data['updated_at'] = $this->updated_at->toDateTimeString();
        $data['price_net'] = $this->undoNormalizePriceNet();
        $data['price_gross'] = $this->undoNormalizePriceGross();

        return $data;
    }
}
