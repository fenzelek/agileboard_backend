<?php

namespace App\Modules\User\Traits;

trait Active
{
    /**
     * Choose only active users.
     *
     * @param \Illuminate\Database\Query\Builder $query
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('deleted', 0)->where('activated', 1);
    }
}
