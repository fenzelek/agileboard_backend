<?php

namespace App\Filters;

use App\Services\Mnabialek\LaravelEloquentFilter\Filters\SimpleQueryFilter;

class StoryFilter extends SimpleQueryFilter
{
    protected $simpleFilters = [
        'id',
        'priority',
    ];

    protected $simpleSorts = [
        'id',
        'name',
        'priority',
        'created_at',
        'updated_at',
    ];

    protected function applyName($value)
    {
        if (is_string($value)) {
            $this->query->where('name', 'like', '%' . $value . '%');
        }
    }
}
