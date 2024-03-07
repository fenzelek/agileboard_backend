<?php

namespace App\Http\Resources;

class Receipt extends AbstractResource
{
    protected $fields = [
        'id',
        'number',
        'transaction_number',
        'user_id',
        'company_id',
        'sale_date',
        'price_net',
        'price_gross',
        'vat_sum',
        'payment_method_id',
        'cash_back',
        'created_at',
        'updated_at',
    ];

    public function toArray($request)
    {
        $data = parent::toArray($request);

        $data['created_at'] = $this->created_at ? $this->created_at->toDateTimeString() : null;
        $data['updated_at'] = $this->updated_at ? $this->updated_at->toDateTimeString() : null;
        $data['cash_back'] = denormalize_price($this->cash_back);
        $data['price_net'] = $this->undoNormalizePriceNet();
        $data['price_gross'] = $this->undoNormalizePriceGross();
        $data['vat_sum'] = $this->undoNormalizeVatSum();

        return $data;
    }
}
