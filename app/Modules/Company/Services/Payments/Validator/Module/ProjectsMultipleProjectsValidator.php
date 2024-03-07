<?php

namespace App\Modules\Company\Services\Payments\Validator\Module;

use App\Models\Db\ModuleMod;
use App\Modules\Company\Services\Payments\Validator\PackageModuleValidator;

class ProjectsMultipleProjectsValidator extends PackageModuleValidator
{
    public function canUpdateCompanyModule(ModuleMod $moduleMod)
    {
        if ($moduleMod->value === ModuleMod::UNLIMITED) {
            return true;
        }

        return $moduleMod->value >= $this->company->projects()->count();
    }
}
