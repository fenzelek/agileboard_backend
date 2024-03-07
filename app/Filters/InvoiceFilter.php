<?php

namespace App\Filters;

use App\Services\Mnabialek\LaravelEloquentFilter\Filters\SimpleQueryFilter;

class InvoiceFilter extends SimpleQueryFilter
{
    protected $simpleFilters = [
        'id',
        'number',
        'contractor_id',
        'drawer_id',
        'proforma_id',
        'invoice_type_id',
        'invoice_registry_id',
    ];

    protected $simpleSorts = [
        'id',
        'number',
        'order_number',
        'sale_date',
        'issue_date',
        'price_net',
        'price_gross',
        'payment_left',
        'created_at',
        'updated_at',
    ];

    protected function applyNumber($value)
    {
        $this->query->where('number', 'like', '%' . $value . '%');
    }
}
