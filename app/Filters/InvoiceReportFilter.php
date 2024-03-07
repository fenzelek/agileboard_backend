<?php

namespace App\Filters;

use App\Services\Mnabialek\LaravelEloquentFilter\Filters\SimpleQueryFilter;

class InvoiceReportFilter extends SimpleQueryFilter
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

    protected function applyNumber($value)
    {
        $this->query->where('number', 'like', '%' . $value . '%');
    }
}
