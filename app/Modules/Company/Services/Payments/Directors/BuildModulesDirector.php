<?php

namespace App\Modules\Company\Services\Payments\Directors;

use App\Models\Db\Company;
use App\Modules\Company\Services\Payments\Interfaces\ModuleBuilder;
use App\Modules\Company\Services\Payments\Interfaces\BuildDirector;

class BuildModulesDirector implements BuildDirector
{
    private $builder;

    public function __construct(ModuleBuilder $moduleBuilder)
    {
        $this->builder = $moduleBuilder;
    }

    public function build(Company $company)
    {
        $this->builder->setCompany($company);
        $this->builder->validate();
        $this->builder->calculatePrices();

        return $this->builder->getModule();
    }
}
