<?php

namespace App\Models\Db;

use App\Modules\SaleOther\Traits\PriceNormalize;

class Receipt extends Model
{
    use PriceNormalize;

    protected $fillable = [
        'number',
        'transaction_number',
        'user_id',
        'company_id',
        'sale_date',
        'price_net',
        'price_gross',
        'vat_sum',
        'payment_method_id',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany(ReceiptItem::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function invoices()
    {
        return $this->belongsToMany(Invoice::class, 'invoice_receipt');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }
}
