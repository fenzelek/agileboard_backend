<?php

namespace App\Models\Db;

use Carbon\Carbon;

class CompanyModuleHistory extends Model
{
    const STATUS_NOT_USED = 0;
    const STATUS_USED = 1;

    protected $table = 'company_modules_history';

    protected $dates = ['start_date', 'expiration_date'];

    protected $guarded = [];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function moduleMod()
    {
        return $this->belongsTo(ModuleMod::class);
    }

    public function modPrice30days()
    {
        return $this->hasOne(ModPrice::class, 'module_mod_id', 'module_mod_id')
            ->where(function ($q) {
                $q->where('days', 30);
                $q->orWhereNull('days');
            })
            ->where('package_id', $this->package_id)
            ->where('currency', $this->currency);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * SCOPE.
     */
    public function scopeIsPending($query)
    {
        return $query->where('status', $this::STATUS_NOT_USED)
            ->whereNotNull('expiration_date')
            ->where('start_date', '>', Carbon::now());
    }
}
