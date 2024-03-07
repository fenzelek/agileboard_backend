<?php

namespace App\Models\Db;

class InvoiceReceipt extends Model
{
    protected $table = 'invoice_receipt';

    protected $fillable = [
        'invoice_id',
        'receipt_id',
    ];
}
