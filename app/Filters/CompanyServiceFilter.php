<?php

namespace App\Filters;

use App\Services\Mnabialek\LaravelEloquentFilter\Filters\SimpleQueryFilter;

class CompanyServiceFilter extends SimpleQueryFilter
{
    protected $simpleFilters = [
        'id',
        'name',
        'type',
        'description',
        'pkwiu',
        'service_unit_id',
        'vat_rate_id',
        'created_id',
        'editor_id',
    ];

    protected $simpleSorts = [
        'id',
        'name',
        'type',
        'description',
        'pkwiu',
        'is_used',
        'created_at',
        'updated_at',
    ];

    protected function applyDefaultSorts()
    {
        $this->query->orderBy('id');
    }

    protected function applyName($name)
    {
        $this->query->where('name', 'like', '%' . $name . '%');
    }
}
