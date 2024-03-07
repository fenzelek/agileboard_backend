<?php

namespace App\Modules\Company\Services\Payments\Validator\Module;

use App\Models\Db\ModuleMod;
use App\Modules\Company\Services\Payments\Validator\PackageModuleValidator;

class GeneralWelcomeUrlValidator extends PackageModuleValidator
{
    public function validate(ModuleMod $moduleMod)
    {
        return true;
    }
}
