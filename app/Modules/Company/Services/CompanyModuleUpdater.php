<?php

namespace App\Modules\Company\Services;

use App\Models\Db\CompanyModuleHistory;
use App\Models\Db\Module;
use App\Models\Db\Package;
use App\Modules\Company\Services\Payments\Validator\ModuleValidatorFactory;
use DB;
use App\Models\Db\CompanyModule;
use App\Models\Db\Transaction;
use App\Models\Other\PaymentStatus;
use App\Models\Db\Payment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\Models\Db\Company as CompanyModel;

class CompanyModuleUpdater
{
    protected $company;

    public function setCompany(CompanyModel $company)
    {
        $this->company = $company;
    }

    public function createHistory(Collection $modules, Carbon $start_date = null)
    {
        $transaction = DB::transaction(function () use ($modules, $start_date) {
            $transaction = Transaction::create();

            $company_module = CompanyModule::where('company_id', $this->company->id)->get();

            foreach ($modules as $module) {
                $current_module = $company_module->where('module_id', $module->id)->first();

                CompanyModuleHistory::create([
                    'company_id' => $this->company->id,
                    'module_id' => $module->id,
                    'module_mod_id' => $module->mods[0]->id,
                    'old_value' => $current_module ? $current_module->value : null,
                    'new_value' => $module->mods[0]->value,
                    'package_id' => $module->mods[0]->modPrices[0]->package_id,
                    'price' => $module->mods[0]->modPrices[0]->price,
                    'currency' => $module->mods[0]->modPrices[0]->currency,
                    'status' => CompanyModuleHistory::STATUS_NOT_USED,
                    'transaction_id' => $transaction->id,
                    'start_date' => $start_date ?: null,
                ]);
            }

            return $transaction;
        });

        return $transaction;
    }

    public function activateWithUpdateHistory($transaction_id, $days = null, Carbon $day_start = null)
    {
        //get all modules
        $module_history = CompanyModuleHistory::where('transaction_id', $transaction_id)
            ->with('moduleMod')
            ->get();

        DB::transaction(function () use ($module_history, $days, $day_start, $transaction_id) {
            //get days
            if ($days === 0) {
                $query = $this->getQueryCompanyModule($module_history[0]->package_id, $module_history[0]->module_id);
                $seconds = Carbon::now()->diffInSeconds(Carbon::parse($query->first()->expiration_date), false);
            } else {
                $seconds = $days * 86400;
            }

            foreach ($module_history as $item) {
                //update history
                $day_start_current = $day_start ?: Carbon::now();
                CompanyModuleHistory::where('id', $item->id)->update([
                    'start_date' => $day_start_current,
                    'expiration_date' => null === $days ? null : (clone $day_start_current)->addSeconds($seconds),
                ]);
            }

            //update companyModules
            if (null === $day_start || $this->isRenewalSame($module_history)) {
                $module_history = CompanyModuleHistory::where('transaction_id', $transaction_id)
                    ->with('moduleMod')
                    ->get();
                $this->active($module_history);
            }
        });
    }

    public function active($module_history)
    {
        //clear
        $only_one = count($module_history) == 1;
        $this->getQueryCompanyModule($module_history[0]->package_id, $module_history[0]->module_id, $only_one)
            ->delete();

        //get subscription_id
        $payment = Payment::where('transaction_id', $module_history[0]->transaction_id)
            ->where('status', PaymentStatus::STATUS_COMPLETED)->first();
        $subscription_id = $payment ? $payment->subscription_id : null;

        $validator_factory = new ModuleValidatorFactory();

        CompanyModuleHistory::where('transaction_id', $module_history[0]->transaction_id)
            ->update(['status' => CompanyModuleHistory::STATUS_USED]);

        $blockade_company = [];
        foreach ($module_history as $item) {
            CompanyModule::create([
                'company_id' => $this->company->id,
                'module_id' => $item->module_id,
                'value' => $item->new_value,
                'package_id' => $item->package_id,
                'expiration_date' => $item->expiration_date,
                'subscription_id' => $subscription_id,
            ]);

            $validator = $validator_factory->create($item->module);
            $validator->setCompany($this->company);
            if (! $validator->canUpdateCompanyModule($item->moduleMod)) {
                $blockade_company [] = $item->module->slug;
            }
        }

        $this->company->blockade_company = count($blockade_company) ? implode(',', $blockade_company) : null;
        $this->company->save();
    }

    public function changeToDefault(CompanyModule $companyModule)
    {
        if ($companyModule->package_id) {
            //package
            $package = Package::findDefault();
            $modules = $package->modules()->with(['mods' => function ($q) use ($package) {
                $q->whereHas('modPrices', function ($q) use ($package) {
                    $q->default('PLN');
                    $q->where('package_id', $package->id);
                });
                $q->with(['modPrices' => function ($q) use ($package) {
                    $q->default('PLN');
                    $q->where('package_id', $package->id);
                }]);
            }])->get();
        } else {
            //external module
            $module = $companyModule->module;

            $module = $module->load(['mods' => function ($q) {
                $q->whereHas('modPrices', function ($q) {
                    $q->default('PLN');
                });
                $q->with(['modPrices' => function ($q) {
                    $q->default('PLN');
                }]);
            }]);
            $modules = collect([$module]);
        }

        $transaction = $this->createHistory($modules);
        $this->activateWithUpdateHistory($transaction->id);
    }

    public function getQueryCompanyModule($package_id, $module_id, $only_one = false)
    {
        if ($package_id && ! $only_one) {
            return CompanyModule::where('company_id', $this->company->id)->whereNotNull('package_id');
        }

        return CompanyModule::where('company_id', $this->company->id)->where('module_id', $module_id);
    }

    public function updateBlockadedCompany($module_type)
    {
        $list = [];

        if (null !== $this->company->blockade_company) {
            $list = explode(',', $this->company->blockade_company);
        }

        $validator_factory = new ModuleValidatorFactory();
        $validator = $validator_factory->create(Module::findBySlug($module_type));
        $validator->setCompany($this->company);

        $module = Module::findBySlug($module_type);
        $moduleMod = $this->company->getCompanyModule($module)->companyModuleHistory->moduleMod;

        if (in_array($module_type, $list)) {
            if ($validator->canUpdateCompanyModule($moduleMod)) {
                array_splice($list, array_search($module_type, $list), 1);
            }
        } else {
            if (! $validator->canUpdateCompanyModule($moduleMod)) {
                $list [] = $module_type;
            }
        }

        $this->company->blockade_company = count($list) ? implode(',', $list) : null;
        $this->company->save();
    }

    private function isRenewalSame($modules_history)
    {
        $module = $modules_history[0];

        $company_module = $this->company->getCompanyModule($module->module);

        if ($module->package_id) {
            return $company_module->package_id == $module->package_id;
        }

        return $company_module->value == $module->new_value;
    }
}
