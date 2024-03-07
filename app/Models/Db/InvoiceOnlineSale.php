<?php

namespace App\Models\Db;

class InvoiceOnlineSale extends Model
{
    protected $table = 'invoice_online_sale';

    protected $fillable = [
        'invoice_id',
        'online_sale_id',
    ];
}
