<?php

namespace App\Models\Db;

class Clipboard extends Model
{
    protected $guarded = [];

    /**
     * Company.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
