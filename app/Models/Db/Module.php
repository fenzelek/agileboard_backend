<?php

namespace App\Models\Db;

class Module extends Model
{
    protected $guarded = [];

    /**
     * Find application setting by slug.
     *
     * @param $slug
     * @return mixed
     */
    public static function findBySlug($slug)
    {
        return self::where('slug', $slug)->first();
    }

    /**
     * RELATIONS.
     */
    public function packages()
    {
        return $this->hasManyThrough(
            Package::class,
            PackageModule::class,
            'module_id',
            'id',
            'id',
            'package_id'
        );
    }

    public function mods()
    {
        return $this->hasMany(ModuleMod::class);
    }

    public function companyModule()
    {
        return $this->hasOne(CompanyModule::class);
    }

    /**
     * SCOPE.
     */
    public function scopeAvailable($q)
    {
        return $q->where('available', '1');
    }

    /**
     * Get testing mods.
     *
     * @return \Illuminate\Support\Collection
     */
    public function testingMods()
    {
        $testing_mods = collect();

        $this->load('mods.modPrices')->mods->each(function ($mod) use ($testing_mods) {
            if ($mod->test) {
                $testing_mods->push($mod);
            }
        });

        return $testing_mods;
    }
}
