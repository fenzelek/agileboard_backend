<?php

namespace App\Models\Db;

use App\Modules\SaleOther\Traits\PriceNormalize;

class ReceiptItem extends Model
{
    use PriceNormalize;

    protected $fillable = [
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
    ];

    public function receipt()
    {
        return $this->belongsTo(Receipt::class);
    }
}
