<?php

namespace App\Models\Db;

class CompanyJpkDetail extends Model
{
    /**
     * @inheritdoc
     */
    protected $guarded = [];

    /**
     * Company might have assigned single tax office.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function taxOffice()
    {
        return $this->belongsTo(TaxOffice::class);
    }
}
