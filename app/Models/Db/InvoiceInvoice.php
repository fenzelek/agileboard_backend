<?php

namespace App\Models\Db;

class InvoiceInvoice extends Model
{
    protected $table = 'invoice_invoice';

    public function parent()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function node()
    {
        return $this->belongsTo(Invoice::class);
    }
}
