<?php

namespace App\Modules\Company\Services\Payments\Validator;

use App\Models\Db\ModuleMod;

abstract class PackageModuleValidator extends ModuleValidator
{
    public function validate(ModuleMod $moduleMod)
    {
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
}
