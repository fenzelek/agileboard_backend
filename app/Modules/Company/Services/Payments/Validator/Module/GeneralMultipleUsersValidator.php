<?php

namespace App\Modules\Company\Services\Payments\Validator\Module;

use App\Models\Db\ModuleMod;
use App\Models\Other\UserCompanyStatus;
use App\Modules\Company\Services\Payments\Validator\PackageModuleValidator;

class GeneralMultipleUsersValidator extends PackageModuleValidator
{
    public function canUpdateCompanyModule(ModuleMod $moduleMod)
    {
        if ($moduleMod->value === ModuleMod::UNLIMITED) {
            return true;
        }

        $count = $this->company->usersCompany()->where('status', UserCompanyStatus::APPROVED)->count();

        return $moduleMod->value >= $count;
    }
}
