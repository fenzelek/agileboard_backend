<?php

namespace App\Models\Db;

use Illuminate\Database\Eloquent\Builder;

class VatRate extends Model
{
    const ZW = 'zw.';
    const NP = 'np.';
    const NP_UE = 'np. UE';
    const TAX_23 = '23%';
    const TAX_22 = '22%';
    const TAX_8 = '8%';
    const TAX_7 = '7%';
    const TAX_5 = '5%';
    const TAX_0 = '0%';
    const TAX_0_WDT = '0% WDT';
    const TAX_0_EXP = '0% EXP';

    public static function findByName($name)
    {
        return self::where('name', trim($name))->firstOrFail();
    }

    /**
     *  Global scope filter vat rates by company vat payer setting.
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('vat_payer', function (Builder $builder) {
            if (auth()->user() && auth()->user()->getSelectedCompanyId() && ! auth()->user()->selectedCompany()->isVatPayer()) {
                $builder->where('name', static::NP);
            }
        });
    }
}
