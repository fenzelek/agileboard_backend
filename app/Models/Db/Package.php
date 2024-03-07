<?php

namespace App\Models\Db;

use App\Modules\SaleInvoice\Traits\FindBySlug;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends Model
{
    use SoftDeletes, FindBySlug;

    const START = 'start';
    const PREMIUM = 'premium';
    const CEP_FREE = 'cep';
    const CEP_CLASSIC = 'cep_classic';
    const CEP_BUSINESS = 'cep_business';
    const CEP_ENTERPRISE = 'cep_enterprise';
    const ICONTROL = 'icontrol';

    /**
     * @inheritdoc
     */
    protected $guarded = [];

    /**
     * Filter current portal.
     *
     * @param $q
     * @return mixed
     */
    public function scopeCurrentPortal($q)
    {
        return $q->where('portal_name', config('app_settings.package_portal_name'));
    }

    /**
     * Package application settings.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function applicationSettings()
    {
        return $this->belongsToMany(ApplicationSetting::class, 'package_application_settings')
            ->withPivot('value')
            ->withTimestamps();
    }

    /**
     * Find default package for company.
     *
     * @return mixed
     */
    public static function findDefault()
    {
        return self::where('default', true)->firstOrFail();
    }

    /**
     * RELATIONS.
     */
    public function modules()
    {
        return $this->hasManyThrough(
            Module::class,
            PackageModule::class,
            'package_id',
            'id',
            'id',
            'module_id'
        )
            ->available();
    }

    public function modPrices()
    {
        return $this->hasMany(ModPrice::class);
    }
}
