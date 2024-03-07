<?php

namespace Tests\Feature\App\Modules\TimeTracker\Http\Controllers\TimeTrackerController\AddScreenshots;

use App\Models\Db\Package;
use App\Models\Other\RoleType;

trait AddScreenshotTrait
{
    /**
     * @param $project
     * @param $company
     * @return string
     */
    public function getPath($project, $company, $separate_folder): string
    {
        return $this->main_folder . '/' . $project->short_name . '_' . str_slug($company->name) . '/' . $this->user->id . '/' . $separate_folder . '/';
    }

    protected function prepareCompany()
    {
        $company = $this->createCompanyWithRoleAndPackage(RoleType::DEVELOPER, Package::CEP_FREE);
        $company->roles()->attach([4]);

        return $company;
    }
}
