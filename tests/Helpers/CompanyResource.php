<?php

namespace Tests\Helpers;

use App\Models\Db\CompanyModule;
use App\Models\Db\Company;
use App\Models\Db\Module;
use Illuminate\Support\Collection;

trait CompanyResource
{
    protected function assignUserRolesToCompany(Collection $roles, Company $company)
    {
        $roles->each(function ($role) use ($company) {
            $role->companies()->attach($company->id);
        });
    }

    /**
     * Set application setting.
     *
     * @param $company
     * @param $setting_slug
     * @param $value
     */
    protected function setAppSettings($company, $setting_slug, $value)
    {
        $setting = Module::findBySlug($setting_slug);

        $mod = $company->companyModules()->where('module_id', $setting->id)->first();
        if ($mod) {
            $mod->value = $value;
            $mod->save();
        } else {
            $module = new CompanyModule();
            $module->company_id = $company->id;
            $module->module_id = $setting->id;
            $module->package_id = $company->realPackage->id;
            $module->value = $value;
            $module->save();
        }
    }
}
