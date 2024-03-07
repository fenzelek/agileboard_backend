<?php

namespace App\Filters;

use App\Services\Mnabialek\LaravelEloquentFilter\Filters\SimpleQueryFilter;

class ProjectUserFilter extends SimpleQueryFilter
{
    use UserSearch;

    /**
     * Allow to filter by user id.
     *
     * @param int $value
     */
    public function applyUserId($value)
    {
        $value = ($value == 'current') ? $this->app['auth']->user()->id : $value;
        $this->query->whereHas('user', function ($q) use ($value) {
            $q->where('id', $value);
        });
    }
}
