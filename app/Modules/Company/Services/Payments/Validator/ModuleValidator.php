<?php

namespace App\Modules\Company\Services\Payments\Validator;

use App\Models\Db\Company;
use App\Models\Db\ModPrice;
use App\Models\Db\ModuleMod;
use App\Modules\Company\Services\Payments\Interfaces\ModuleValidator as ModuleValidatorInterface;
use Carbon\Carbon;

abstract class ModuleValidator implements ModuleValidatorInterface
{
    const MIN_DAY_AMOUNT = 30;
    protected $company;

    /**
     * Set company.
     *
     * @param Company $company
     */
    public function setCompany(Company $company)
    {
        $this->company = $company;
    }

    /**
     * Check company can`t test module.
     *
     * @param ModuleMod $mod
     * @return bool
     */
    public function wasTested(ModuleMod $mod)
    {
        if (! $mod->test) {
            return false;
        }

        //get all testing modules
        $testing_mods = $mod->module->mods()->testing()->pluck('id');

        //check used
        if ($this->company->companyModulesHistory()->whereIn('module_mod_id', $testing_mods)->first()) {
            return true;
        }

        //disable testing when premium mod is active
        if ($this->company->hasActivePremiumMod($mod->module)) {
            return true;
        }

        return false;
    }

    public function isWaitingForPayment(ModuleMod $mod)
    {
        $module = $this->company->companyModulesHistory()->where('module_id', $mod->module_id)->first();
        if (! $module->transaction) {
            return false;
        }
        $payment = $module->transaction->payments()->orderByDesc('id')->first();
        if ($payment && $payment->isWaitingForPayment()) {
            return true;
        }

        return false;
    }

    public function canUpdateCompanyModule(ModuleMod $moduleMod)
    {
        return true;
    }

    public function canChangeNow(ModuleMod $moduleMod, ModPrice $modPrice)
    {
        $company_module = $this->company->getCompanyModule($moduleMod->module);

        if ($company_module->subscription_id && $company_module->subscription->active) {
            return false;
        }

        if ((! $moduleMod->error || $this->isOtherPackage($moduleMod, $modPrice)) &&
            null !== $company_module->expiration_date && Carbon::now()->lt($company_module->expiration_date)) {
            $expiration_days = $this->company->packageExpirationInDays();

            foreach (ModPrice::INTERVALS as $interval) {
                if ($modPrice->days <= $interval[0] && $modPrice->days > $interval[1] &&
                    $expiration_days <= $interval[0] && $expiration_days > $interval[1]) {
                    return true;
                }
            }
        }

        return false;
    }

    public function canRenew(ModuleMod $moduleMod, ModPrice $modPrice)
    {
        $company_module = $this->company->getCompanyModule($moduleMod->module);

        if ($company_module->subscription_id && $company_module->subscription->active) {
            return false;
        }

        if ($this->company->hasPendingMod($moduleMod)) {
            return false;
        }

        if (! $moduleMod->error && $this->company->getExpirationDaysActiveMod($moduleMod) < self::MIN_DAY_AMOUNT) {
            return true;
        }

        return $moduleMod->error == ValidatorErrors::MODULE_MOD_CURRENTLY_USED_CAN_EXTEND;
    }

    private function isOtherPackage(ModuleMod $moduleMod, ModPrice $modPrice)
    {
        if ($moduleMod->error != ValidatorErrors::MODULE_MOD_CURRENTLY_USED &&
            $moduleMod->error != ValidatorErrors::MODULE_MOD_CURRENTLY_USED_CAN_EXTEND) {
            return false;
        }

        if ($modPrice->package_id && $modPrice->package_id != $this->company->realPackage()->id) {
            return true;
        }

        return false;
    }
}
