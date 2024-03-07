<?php

namespace App\Modules\Company\Services\Payments\Interfaces;

use App\Models\Db\Company;

interface BuildDirector
{
    public function __construct(ModuleBuilder $moduleBuilder);

    public function build(Company $company);
}
