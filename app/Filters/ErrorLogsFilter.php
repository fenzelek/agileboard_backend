<?php

namespace App\Filters;

use App\Services\Mnabialek\LaravelEloquentFilter\Filters\SimpleQueryFilter;

class ErrorLogsFilter extends SimpleQueryFilter
{
    /**
     * @var array
     */
    protected $simpleFilters = [
        'user_id',
    ];

    /**
     * @var array
     */
    protected $simpleSorts = [
        'id',
        'company_id',
        'user_id',
        'transaction_number',
        'url',
        'method',
        'request',
        'status_code',
        'response',
        'request_date',
        'created_at',
        'updated_at',
    ];

    /**
     * Custom filer for request column. It finds given strings (in array) in request string.
     *
     * @param array $value
     */
    protected function applyRequest($value)
    {
        $this->query->where(function ($query) use ($value) {
            foreach ($value as $word) {
                $query->orWhere('request', 'like', '%' . trim($word) . '%');
            }
        });
    }

    /**
     * Custom filer for url column. It finds given string in url string.
     *
     * @param string $value
     */
    protected function applyUrl($value)
    {
        $this->query->where('url', 'like', '%' . trim($value) . '%');
    }
}
