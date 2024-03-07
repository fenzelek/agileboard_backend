<?php

namespace App\Modules\Company\Services\Payments\Validator;

use App\Models\Db\ModPrice;
use App\Models\Db\ModuleMod;

abstract class ExtendModuleValidator extends ModuleValidator
{
    public function validate(ModuleMod $moduleMod)
    {
        //is default/free mod
        if (in_array($moduleMod->value, ['0', '', null])) {
            return false;
        }

        //check is testing module and was tested
        if ($this->wasTested($moduleMod)) {
            return false;
        }

        //is current used
        if ($this->company->hasActiveMod($moduleMod)) {
            if ($this->company->getExpirationDaysActiveMod($moduleMod) < self::MIN_DAY_AMOUNT &&
                ! $this->company->hasActiveModWithSubscription($moduleMod)) {
                return ValidatorErrors::MODULE_MOD_CURRENTLY_USED_CAN_EXTEND;
            }

            return ValidatorErrors::MODULE_MOD_CURRENTLY_USED;
        }

        //cen buy module only in premium packages
        if (! $this->company->hasPremiumPackage()) {
            return ValidatorErrors::FREE_PACKAGE_NOW_USED;
        }

        //this module has mod who waiting for payment
        if ($this->isWaitingForPayment($moduleMod)) {
            return ValidatorErrors::WAITING_FOR_PAYMENT;
        }

        //this module has less items than currently used
        if (! $this->canUpdateCompanyModule($moduleMod)) {
            return ValidatorErrors::UNAVAILABLE_VALUE;
        }

        return true;
    }

    public function canRenew(ModuleMod $moduleMod, ModPrice $modPrice)
    {
        if ($this->company->hasTestPackage()) {
            return false;
        }

        if (parent::canRenew($moduleMod, $modPrice)) {
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
}
