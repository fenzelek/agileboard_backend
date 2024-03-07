<?php

namespace App\Filters;

trait EmptyFieldSearch
{
    /**
     * Add empty field queries.
     *
     * @param string $field
     * @param int|string $value
     */
    protected function addEmptyFieldQueries($field, $value)
    {
        // no value, nothing to do
        if (! $value) {
            return;
        }

        ($value === static::EMPTY) ? $this->query->whereNull($field) :
            $this->query->where($field, (int) $value);
    }
}
