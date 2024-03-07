<?php

namespace App\Modules\Company\Services\Payments;

use App\Models\Db\CompanyModule;
use App\Models\Db\CompanyModuleHistory;
use App\Models\Db\Module;
use App\Models\Db\Package;
use App\Modules\Company\Services\CompanyModuleUpdater;
use App\Modules\Company\Services\Payments\Directors\BuildModulesDirector;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Collection;

class ModuleService
{
    /**
     * @param Guard $auth
     * @return mixed
     */
    public function getCurrentActiveModules(Guard $auth)
    {
        return $auth->user()->selectedCompany()->companyModules()
            ->whereNull('package_id')
            ->notFree()
            ->with('module')
            ->get();
    }

    /**
     * @param Module $module
     * @return mixed
     */
    public function getExternalModules(Module $module)
    {
        return $module->available()
            ->with(['mods' => function ($q) {
                $q->whereHas('modPrices', function ($q) {
                    $q->whereNull('package_id');
                });
                $q->with(['modPrices' => function ($q) {
                    $q->whereNull('package_id');
                }]);
            }])
            ->get();
    }

    /**
     * @param Package $package
     * @return mixed
     */
    public function getPackageModules(Package $package)
    {
        return $package->modules()->available()
            ->with(['mods' => function ($q) use ($package) {
                $q->whereHas('modPrices', function ($q) use ($package) {
                    $q->where('package_id', $package->id);
                });
                $q->with(['modPrices' => function ($q) use ($package) {
                    $q->where('package_id', $package->id);
                }]);
            }])
            ->get();
    }

    /**
     * @param $modules
     * @param $auth
     * @param $validatorFactory
     * @param $builderFactory
     * @return array|Collection
     */
    public function getBuiltModules($modules, $auth, $validatorFactory, $builderFactory)
    {
        $company = $auth->user()->selectedCompany();

        $modules_available = new Collection([]);
        foreach ($modules as $module) {
            $params = ['validator' => $validatorFactory->create($module)];
            $director = new BuildModulesDirector($builderFactory->create($module, $params));
            $new_module = $director->build($company);
            if (count($new_module->mods)) {
                $modules_available [] = $new_module;
            }
        }

        return $modules_available;
    }

    /**
     * @param $updater
     * @param $company
     * @param $payment
     * @param $modules
     * @param $paymentService
     * @param $price
     * @param $vat
     * @param $params
     * @return mixed
     */
    public function moduleStaffBeforeRebuild($updater, $company, $payment, $modules, $paymentService, $price, $vat, $params)
    {
        $updater->setCompany($company);

        if ($price) {
            $company_module = $updater
                ->getQueryCompanyModule($modules[0]->mods[0]->modPrices[0]->package_id, $modules[0]->id)->first();

            //start_date if renew
            $start_date = null;
            if ($params['days'] != 0 && $company_module->expiration_date &&
                Carbon::now()->lt($company_module->expiration_date)) {
                $start_date = $company_module->expiration_date;
            }

            $transaction = $updater->createHistory($modules, $start_date);

            //expiration payment
            $expiration_date = $params['days'] == 0 ? $company_module->expiration_date : null; //if change

            if (! $expiration_date && ! $company_module->package_id) {
                $expiration_date = $company->realPackageExpiringDate(); //if external_module
            }

            //days
            $days = $params['days'] ?: null;
            if ($days && ! $company_module->package_id && $days > $company->packageExpirationInDays()) {
                $days = $company->packageExpirationInDays();
            }

            //payments required
            $paymentService->preparePayments(
                $payment,
                $transaction->id,
                $price,
                $vat,
                $modules[0]->mods[0]->modPrices[0]->currency,
                $days,
                null,
                $expiration_date
            );
        } else {
            //free
            $transaction = $updater->createHistory($modules);

            $days = $modules[0]->mods[0]->modPrices[0]->days;
            if (! $params['days'] && ! $params['is_test']) {
                $days = 0;
            }

            $updater->activateWithUpdateHistory($transaction->id, $days);
        }

        return $transaction;
    }

    /**
     * @param CompanyModule $companyModule
     * @param CompanyModuleUpdater $updater
     */
    public function changeToDefault(CompanyModule $companyModule, CompanyModuleUpdater $updater)
    {
        //packages
        $companyModule
            ->where('expiration_date', '<', Carbon::now())
            ->where(function ($q) {
                $q->whereNull('subscription_id');
                $q->orWhereHas('subscription', function ($q) {
                    $q->where('active', false);
                });
            })
            ->whereNotNull('package_id')
            ->groupBy('company_id')
            ->get()->each(function ($module) use ($updater) {
                $updater->setCompany($module->company);
                $updater->changeToDefault($module);
            });

        //modules
        $companyModule
            ->where('expiration_date', '<', Carbon::now())
            ->where(function ($q) {
                $q->whereNull('subscription_id');
                $q->orWhereHas('subscription', function ($q) {
                    $q->where('active', false);
                });
            })
            ->whereNull('package_id')
            ->get()->each(function ($module) use ($updater) {
                $updater->setCompany($module->company);
                $updater->changeToDefault($module);
            });
    }

    /**
     * @param CompanyModuleUpdater $updater
     * @param CompanyModuleHistory $history
     */
    public function activateModules(CompanyModuleUpdater $updater, CompanyModuleHistory $history)
    {
        $list = $history->where('status', CompanyModuleHistory::STATUS_NOT_USED)
            ->where('start_date', '<', Carbon::now())
            ->where('expiration_date', '>', Carbon::now())
            ->groupBy('transaction_id')
            ->get();

        foreach ($list as $item) {
            $modules = $history->where('transaction_id', $item->transaction_id)->with('moduleMod')->get();
            $updater->setCompany($item->company);
            $updater->active($modules);
        }
    }
}
