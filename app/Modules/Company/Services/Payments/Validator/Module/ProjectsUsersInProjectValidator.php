<?php

namespace App\Modules\Company\Services\Payments\Validator\Module;

use App\Models\Db\ModuleMod;
use App\Models\Db\Project;
use App\Modules\Company\Services\Payments\Validator\PackageModuleValidator;

class ProjectsUsersInProjectValidator extends PackageModuleValidator
{
    public function canUpdateCompanyModule(ModuleMod $moduleMod)
    {
        if ($moduleMod->value === ModuleMod::UNLIMITED) {
            return true;
        }

        $projects = Project::where('company_id', $this->company->id)->withCount('users')->get();

        foreach ($projects as $project) {
            if ($moduleMod->value < $project->users_count) {
                return false;
            }
        }

        return true;
    }
}
