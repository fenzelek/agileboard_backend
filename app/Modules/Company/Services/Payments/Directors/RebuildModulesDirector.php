<?php

namespace App\Modules\Company\Services\Payments\Directors;

use App\Models\Db\Company;
use App\Modules\Company\Services\Payments\Interfaces\BuildDirector;
use App\Modules\Company\Services\Payments\Interfaces\ModuleBuilder;

class RebuildModulesDirector implements BuildDirector
{
    private $builder;

    public function __construct(ModuleBuilder $moduleBuilder)
    {
        $this->builder = $moduleBuilder;
    }

    public function build(Company $company)
    {
        $this->builder->setCompany($company);
        $this->builder->calculatePrices();
        $this->builder->validate();

        if ($this->builder->hasErrors()) {
            return $this->builder->getErrors();
        }

        return $this->builder->getModule();
    }
}
