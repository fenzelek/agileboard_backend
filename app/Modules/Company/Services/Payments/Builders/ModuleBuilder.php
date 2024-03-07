<?php

namespace App\Modules\Company\Services\Payments\Builders;

use App\Models\Db\Company;
use App\Models\Db\ModPrice;
use App\Modules\Company\Services\Payments\Crypter;
use App\Modules\Company\Services\Payments\Interfaces\ModuleBuilder as ModuleBuilderInterface;
use App\Models\Db\Module;
use Illuminate\Support\Collection;

class ModuleBuilder implements ModuleBuilderInterface
{
    protected $company;

    protected $module;

    protected $mods;

    protected $validator;

    public function __construct(Module $module, array $added_params)
    {
        $this->module = $module;
        $this->mods = new Collection([]);
        $this->validator = array_get($added_params, 'validator', null);
    }

    public function setCompany(Company $company)
    {
        $this->company = $company;
    }

    public function getModule(): Module
    {
        $this->prepareModsChecksum();
        $this->module->setRelation('mods', $this->mods);

        return $this->module;
    }

    public function validate()
    {
        if (! $this->validator) {
            return true;
        }
        $this->validator->setCompany($this->company);
        $this->module->mods->each(function ($mod) {
            if ($info = $this->validator->validate($mod)) {
                $mod->error = $info === true ? false : $info;
                $this->mods->push($mod);
            }
        });

        return true;
    }

    public function calculatePrices()
    {
        $this->mods->each(function ($mod) {
            foreach ($mod->modPrices as $key => $mod_price) {
                $module_id = $mod_price->moduleMod->module->id;

                if ($this->company->hasPremiumPackage() || $this->company->hasPremiumModule($module_id)) {
                    $mod->modPrices[$key]->price_change = $this->diffChangePrice($mod_price);
                } else {
                    $mod->modPrices[$key]->price_change = $mod->modPrices[$key]->price;
                }

                $mod->modPrices[$key]->price = $this->diffPrice($mod_price);
            }
        });
    }

    protected function diffPrice(ModPrice $mod_price)
    {
        if ($mod_price->package_id) {
            return $mod_price->price;
        }

        $days = $this->company->packageExpirationInDays();

        if ($days <= 0 || $days >= $mod_price->days) {
            return $mod_price->price;
        }
        $day_price = $mod_price->price / $mod_price->days;

        return (int) round($day_price * $days);
    }

    protected function diffChangePrice(ModPrice $mod_price)
    {
        $days = $this->company->getCompanyModule($mod_price->moduleMod->module)->moduleDaysLeft();
        if ($days <= 0 || $days >= $mod_price->days) {
            return $mod_price->price;
        }
        $day_price = $mod_price->price / $mod_price->days;

        return (int) round($day_price * $days);
    }

    private function prepareModsChecksum()
    {
        $this->mods->each(function ($mod) {
            $mod->modPrices->each(function ($modPrice) use ($mod) {
                //checksum
                if ($this->validator->canRenew($mod, $modPrice)) {
                    $modPrice->checksum = Crypter::encrypt($modPrice->id, $modPrice->price);
                } else {
                    $modPrice->checksum = null;
                }

                //checksum_change
                if ($this->validator->canChangeNow($mod, $modPrice)) {
                    $modPrice->checksum_change = Crypter::encrypt($modPrice->id, $modPrice->price_change);
                } else {
                    $modPrice->checksum_change = null;
                }
            });
        });
    }
}
