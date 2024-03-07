<?php

namespace App\Models\Db;

trait FullVatin
{
    /**
     * Resource has one vatin prefix.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function vatinPrefix()
    {
        return $this->belongsTo(CountryVatinPrefix::class, 'country_vatin_prefix_id');
    }

    /**
     * Returns vatin with prefix or.
     * @return string|false
     */
    public function getFullVatinAttribute()
    {
        if ($this->vatinPrefix && $this->vatin) {
            return $this->vatinPrefix->key . $this->vatin;
        }

        return $this->vatin;
    }
}
