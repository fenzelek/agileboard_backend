<?php

namespace App\Modules\Company\Services\Payments\Interfaces;

use App\Models\Db\Company;
use App\Models\Db\Module;

interface ModuleBuilder
{
    public function setCompany(Company $company);

    public function validate();

    public function calculatePrices();

    public function getModule(): Module;
}
