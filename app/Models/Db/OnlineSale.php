<?php

namespace App\Models\Db;

use App\Modules\SaleOther\Traits\PriceNormalize;

class OnlineSale extends Model
{
    use PriceNormalize;

    protected $fillable = [
        'email',
        'number',
        'transaction_number',
        'company_id',
        'sale_date',
        'price_net',
        'price_gross',
        'vat_sum',
        'payment_method_id',
    ];

    public function items()
    {
        return $this->hasMany(OnlineSaleItem::class);
    }

    public function invoices()
    {
        return $this->belongsToMany(Invoice::class, 'invoice_online_sale');
    }
}
