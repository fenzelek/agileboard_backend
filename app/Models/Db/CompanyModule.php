<?php

namespace App\Models\Db;

use Carbon\Carbon;

class CompanyModule extends Model
{
    protected $guarded = [];

    protected $dates = ['expiration_date'];

    /**
     * RELATIONS.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function companyModuleHistory()
    {
        return $this->hasOne(CompanyModuleHistory::class, 'company_id', 'company_id')
            ->where('module_id', $this->module_id)
            ->where('expiration_date', $this->expiration_date)
            ->where('new_value', $this->value)
            ->orderBy('id', 'desc');
    }

    /**
     * METHODS.
     */
    public function moduleDaysLeft()
    {
        return Carbon::now()->diffInDays(Carbon::parse($this->expiration_date), false);
    }

    /**
     * SCOPES.
     */
    public function scopeForNotifications($query, $day, $package)
    {
        $time = Carbon::now()->addDays($day);
        $timeStart = Carbon::create($time->year, $time->month, $time->day, $time->hour, 0, 0);
        $timeEnd = Carbon::create($time->year, $time->month, $time->day + 1, $time->hour, 0, 0);

        $query = $query->select('company_modules.*')
            ->where('expiration_date', '>=', $timeStart)
            ->where('expiration_date', '<', $timeEnd);

        if ($package) {
            $query = $query->whereNotNull('package_id');
            $query = $query->groupBy('package_id');
        } else {
            $query = $query->whereNull('package_id');
        }

        return $query;
    }

    public function scopeNotFree($q)
    {
        return $q->where(function ($q) {
            $q->where('value', '!=', '0');
            $q->where('value', '!=', '');
            $q->whereNotNull('value');
        });
    }
}
