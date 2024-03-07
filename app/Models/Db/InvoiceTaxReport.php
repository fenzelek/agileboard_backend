<?php

namespace App\Models\Db;

use App\Modules\SaleOther\Traits\PriceNormalize;

class InvoiceTaxReport extends Model
{
    use PriceNormalize;

    protected $fillable = [
        'invoice_id',
        'vat_rate_id',
        'price_net',
        'price_gross',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function vatRate()
    {
        return $this->belongsTo(VatRate::class);
    }
}
