<?php

namespace App\Http\Resources;

class OnlineSale extends AbstractResource
{
    protected $fields = [
        'id',
        'email',
        'number',
        'transaction_number',
        'company_id',
        'sale_date',
        'price_net',
        'price_gross',
        'vat_sum',
        'payment_method_id',
        'created_at',
        'updated_at',
    ];

    public function toArray($request)
    {
        $data = parent::toArray($request);

        $data['created_at'] = $this->created_at ? $this->created_at->toDateTimeString() : null;
        $data['updated_at'] = $this->updated_at ? $this->updated_at->toDateTimeString() : null;
        $data['price_net'] = $this->resource->undoNormalizePriceNet();
        $data['price_gross'] = $this->resource->undoNormalizePriceGross();
        $data['vat_sum'] = $this->resource->undoNormalizeVatSum();

        return $data;
    }
}
