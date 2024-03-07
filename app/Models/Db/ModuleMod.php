<?php

namespace App\Models\Db;

class ModuleMod extends Model
{
    const UNLIMITED = 'unlimited';
    protected $guarded = [];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function modPrices()
    {
        return $this->hasMany(ModPrice::class);
    }

    /**
     * SCOPE.
     */
    public function scopeTesting($q)
    {
        return $q->where('test', '1');
    }
}
