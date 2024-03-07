<?php

namespace App\Modules\Company\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Helpers\ErrorCode;
use App\Http\Resources\CompanyModulesWithoutHistory;
use App\Http\Resources\ModPriceWitchChecksum;
use App\Http\Resources\ModuleModWithoutModule;
use App\Http\Resources\ModuleWithModsCount;
use App\Http\Resources\PackageWithPrice;
use App\Http\Resources\PackageWithPriceAndSubscriptionInfo;
use App\Models\Db\CompanyModule;
use App\Models\Db\ModPrice;
use App\Models\Db\Module;
use App\Models\Db\ModuleMod;
use App\Models\Db\Package;
use App\Models\Db\Payment;
use App\Modules\Company\Http\Requests\PackageStore;
use App\Modules\Company\Services\CompanyModuleUpdater;
use App\Modules\Company\Services\Payments\Directors\RebuildModulesDirector;
use App\Modules\Company\Services\Payments\ModuleService;
use App\Modules\Company\Services\Payments\Builders\ModuleBuilderFactory;
use App\Modules\Company\Services\Payments\Validator\ModuleValidatorFactory;
use App\Modules\Company\Services\PaymentService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;

class PackageController
{
    /**
     * List all packages.
     *
     * @param Package $package
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Package $package, Request $request)
    {
        $packages = $package->currentPortal()->get();

        foreach ($packages as $package) {
            $package->days = $package->modPrices[0]->days;
            $package->price = $package->modPrices()->default($request->input('currency', 'PLN'))
                ->whereHas('moduleMod.module', function ($q) {
                    $q->available();
                })->sum('price');
        }

        return ApiResponse::transResponseOk($packages, 200, [Package::class => PackageWithPrice::class]);
    }

    /**
     * Current package.
     *
     * @param Guard $auth
     * @return \Illuminate\Http\JsonResponse,
     */
    public function current(Guard $auth)
    {
        $company_id = $auth->user()->getSelectedCompanyId();
        $package = $auth->user()->selectedCompany()->realPackage()->load(['modules.companyModule' => function ($q) use ($auth) {
            $q->where('company_id', $auth->user()->getSelectedCompanyId());
        }]);

        $package->price = 0;
        $company_module = $package->modules[0]->companyModule()->where('company_id', $company_id)->first();
        $history = $company_module->companyModuleHistory;
        $package->days = $history->start_date ? $history->start_date->diffInDays($history->expiration_date, false) : null;
        $package->setRelation('subscription', $company_module->subscription);

        $modules = $package->modules()->available()->with([
            'companyModule' => function ($q) use ($company_id) {
                $q->where('company_id', $company_id);
            },
        ])->get();

        foreach ($modules as $key => $module) {
            $package->price += $module->companyModule->companyModuleHistory->modPrice30days->price;
            $module->mods_count = $module->mods()->where('test', false)
                ->whereHas('modPrices', function ($q) use ($package) {
                    $q->where('package_id', $package->id);
                })->count();
        }

        $package->setRelation('modules', $modules);

        return ApiResponse::transResponseOk($package, 200, [
            Package::class => PackageWithPriceAndSubscriptionInfo::class,
            Module::class => ModuleWithModsCount::class,
            CompanyModule::class => CompanyModulesWithoutHistory::class,
        ]);
    }

    /**
     * Available module mods for buy.
     *
     * @param Package $package
     * @param Guard $auth
     * @param ModuleService $service
     * @param ModuleValidatorFactory $validatorFactory
     * @param ModuleBuilderFactory $builderFactory
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function show(Package $package, Guard $auth, ModuleService $service, ModuleValidatorFactory $validatorFactory, ModuleBuilderFactory $builderFactory)
    {
        if ($package->portal_name != config('app_settings.package_portal_name')) {
            return ApiResponse::responseError(ErrorCode::RESOURCE_NOT_FOUND, 404);
        }

        $modules = $service->getPackageModules($package);

        $modules_available = $service->getBuiltModules($modules, $auth, $validatorFactory, $builderFactory);

        return ApiResponse::transResponseOk($modules_available, 200, [
            ModuleMod::class => ModuleModWithoutModule::class,
            ModPrice::class => ModPriceWitchChecksum::class,
        ]);
    }

    /**
     * Buy package.
     *
     * @param PackageStore $request
     * @param Guard $auth
     * @param ModuleBuilderFactory $builderFactory
     * @param CompanyModuleUpdater $updater
     * @param PaymentService $paymentService
     * @param Payment $payment
     * @param ModuleService $moduleService
     * @param Package $package
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(
        PackageStore $request,
        Guard $auth,
        ModuleBuilderFactory $builderFactory,
        CompanyModuleUpdater $updater,
        PaymentService $paymentService,
        Payment $payment,
        ModuleService $moduleService,
        Package $package
    ) {
        $company = $auth->user()->selectedCompany();

        $params = $request->only(['days', 'is_test']);

        //rebuild
        $modules = new Collection([]);
        foreach ($request->input('mod_price') as $mod_price) {
            $params['checksum'] = $mod_price['checksum'];
            $director = new RebuildModulesDirector($builderFactory->create($mod_price['id'], $params));
            $new_module = $director->build($company);
            if (is_array($new_module)) {
                return ApiResponse::responseError(ErrorCode::PACKAGE_DATA_CONSISTENCY_ERROR, 409);
            }
            $modules [] = $new_module;
        }

        //validate package
        $ids_current = $modules->pluck('id')->toArray();
        $ids_expected = $package->findOrFail($request->input('package_id'))->modules()
            ->available()->pluck('id')->toArray();
        if (count(array_diff($ids_expected, $ids_current)) || count(array_diff($ids_current, $ids_expected))) {
            return ApiResponse::responseError(ErrorCode::PACKAGE_DATA_CONSISTENCY_ERROR, 409);
        }

        //calculate price
        $price = 0;
        foreach ($modules as $module) {
            $price += $module->mods[0]->modPrices[0]->price;
        }
        $vat = (int) ceil($price * 23 / 123);
        $transaction = $moduleService->moduleStaffBeforeRebuild($updater, $company, $payment, $modules, $paymentService, $price, $vat, $params);

        return ApiResponse::responseOk($transaction->load('payments'));
    }
}
