<?php

namespace App\Models\Db;

class ModPrice extends Model
{
    const MAX_DAYS = 395; //365 + 30
    const YEAR_DAYS = 365;
    const MONTH_DAYS = 30;

    const INTERVALS = [
        [self::MAX_DAYS, self::MONTH_DAYS],
        [self::MONTH_DAYS, 0],
    ];
    protected $guarded = [];

    public function moduleMod()
    {
        return $this->belongsTo(ModuleMod::class);
    }

    public function scopeDefault($query, $currency)
    {
        return $query->where('default', 1)->where('currency', $currency);
    }
}
