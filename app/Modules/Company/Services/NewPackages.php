<?php

namespace App\Modules\Company\Services;

use App\Models\Db\Module;
use App\Models\Db\Package;
use Illuminate\Support\Collection;

class NewPackages
{
    private $company;

    private $packages;

    /**
     * @param mixed $packages
     */
    public function __construct()
    {
        $this->packages = new Collection();
    }

    /**
     * @param mixed $company
     */
    public function setCompany($company)
    {
        $this->company = $company;
    }

    /**
     * @return mixed
     */
    public function getPackages()
    {
        $packages = Package::with(['modules' => function ($query) {
            $query->where('visible', true);
        }])
            ->where('id', '!=', $this->company->realPackage()->id)
            ->get();
        $this->preparePackage($packages);

        return $this->packages;
    }

    private function preparePackage(Collection $packages)
    {
        foreach ($packages as $package) {
            $package = $this->setDisplayable($package);
            $package->modules = $this->setWaiting($package->modules);
            $this->packages->push($package);
        }
    }

    private function setWaiting(Collection $modules)
    {
        foreach ($modules as $module) {
            if ($this->moduleIsAwaiting($module)) {
                $module->waiting = true;
            } else {
                $module->waiting = false;
            }
        }

        return $modules;
    }

    private function setDisplayable(Package $package)
    {
        if ($package->isFree()) {
            $package->display_modules = false;
        } else {
            $package->display_modules = true;
        }

        return $package;
    }

    private function moduleIsAwaiting(Module $module)
    {
        if (in_array($module->id, $this->company->awaitingPackageModules()->pluck('module_id')->toArray())) {
            return true;
        }

        return false;
    }
}
