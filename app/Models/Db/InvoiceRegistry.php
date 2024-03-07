<?php

namespace App\Models\Db;

class InvoiceRegistry extends Model
{
    protected $fillable = [
        'invoice_format_id',
        'name',
        'default',
        'prefix',
        'is_used',
        'start_number',
        'company_id',
        'creator_id',
        'editor_id',
    ];

    public function invoiceFormat()
    {
        return $this->belongsTo(InvoiceFormat::class);
    }

    /**
     * Registry can be attached to many invoices.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'invoice_registry_id');
    }
}
