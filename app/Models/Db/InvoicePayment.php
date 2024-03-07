<?php

namespace App\Models\Db;

use App\Modules\SaleInvoice\Traits\PriceNormalize;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoicePayment extends Model
{
    use PriceNormalize;
    use SoftDeletes;

    protected $fillable = [
        'invoice_id',
        'amount',
        'payment_method_id',
        'special_partial_payment',
        'registrar_id',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
