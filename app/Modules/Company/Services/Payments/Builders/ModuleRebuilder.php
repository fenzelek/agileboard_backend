<?php

namespace App\Modules\Company\Services\Payments\Builders;

use App\Models\Db\Company;
use App\Modules\Company\Services\Payments\Crypter;
use App\Modules\Company\Services\Payments\Interfaces\ErrorsStaff;
use App\Modules\Company\Services\Payments\Interfaces\ModuleBuilder as ModuleBuilderInterface;
use App\Models\Db\Module;
use App\Modules\Company\Services\Payments\Validator\ValidatorErrors;

class ModuleRebuilder implements ModuleBuilderInterface, ErrorsStaff
{
    protected $company;

    protected $module;

    protected $added_params;

    protected $errors = [];

    public function __construct(Module $module, array $added_params)
    {
        $this->module = $module;
        $this->added_params = $added_params;
    }

    public function setCompany(Company $company)
    {
        $this->company = $company;
    }

    public function getModule(): Module
    {
        return $this->module;
    }

    public function validate()
    {
        //check checksum
        $data = Crypter::decrypt(array_get($this->added_params, 'checksum', ''));

        if (! $data) {
            $this->errors [] = ValidatorErrors::WRONG_CHECKSUM;
        } else {
            $mod_prices = $this->module->mods[0]->modPrices[0];
            if ($data['id'] != $mod_prices->id || $data['price'] != $mod_prices->price) {
                $this->errors [] = ValidatorErrors::WRONG_CHECKSUM;
            }
        }

        //check test (trial)
        if ($this->module->mods[0]->test != array_get($this->added_params, 'is_test')) {
            $this->errors [] = ValidatorErrors::WRONG_DATA;
        }
    }

    public function calculatePrices()
    {
        $days_from_params = array_get($this->added_params, 'days');
        $mod_price = $this->module->mods[0]->modPrices[0];

        //change
        if (! $days_from_params &&
            ($this->company->hasPremiumPackage() || $this->company->hasPremiumModule($this->module->id))) {
            $days = $this->company->getCompanyModule($mod_price->moduleMod->module)->moduleDaysLeft();
            if ($days <= 0 || $days >= $mod_price->days) {
                return;
            }
            $day_price = $mod_price->price / $mod_price->days;
            $this->module->mods[0]->modPrices[0]->price = (int) round($day_price * $days);

            $vat = $this->module->mods[0]->modPrices[0]->price * 23 / 123;
            $this->module->mods[0]->modPrices[0]->vat = (int) ceil($vat);

            return;
        }

        //new buy extend module
        if ($days_from_params && ! $mod_price->package_id) {
            $days = $this->company->packageExpirationInDays();
            if ($days <= 0 || $days >= $mod_price->days) {
                return;
            }
            $day_price = $mod_price->price / $mod_price->days;
            $this->module->mods[0]->modPrices[0]->price = (int) round($day_price * $days);

            $vat = $this->module->mods[0]->modPrices[0]->price * 23 / 123;
            $this->module->mods[0]->modPrices[0]->vat = (int) ceil($vat);

            return;
        }
    }

    public function hasErrors(): bool
    {
        return (bool) count($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
