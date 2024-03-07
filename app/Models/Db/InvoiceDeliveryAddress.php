<?php

namespace App\Models\Db;

class InvoiceDeliveryAddress extends Model
{
    protected $guarded = [];

    /**
     * Relation belongs to invoice.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
