<?php

namespace App\Modules\Company\Services\Payments\Interfaces;

use App\Models\Db\ModPrice;
use App\Models\Db\ModuleMod;

interface ModuleValidator
{
    public function validate(ModuleMod $moduleMod);

    public function canUpdateCompanyModule(ModuleMod $moduleMod);

    public function canChangeNow(ModuleMod $moduleMod, ModPrice $modPrice);

    public function canRenew(ModuleMod $moduleMod, ModPrice $modPrice);
}
