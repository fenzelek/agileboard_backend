<?php

namespace App\Modules\Company\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Helpers\ErrorCode;
use App\Http\Controllers\Controller;
use App\Http\Resources\ModPriceWitchChecksum;
use App\Http\Resources\ModuleModWithoutModule;
use App\Models\Db\ModPrice;
use App\Models\Db\ModuleMod;
use App\Models\Db\Module;
use App\Models\Db\Payment;
use App\Models\Other\ModuleType;
use App\Models\Other\UserCompanyStatus;
use App\Modules\Company\Http\Requests\ModuleStore;
use App\Modules\Company\Services\CompanyModuleUpdater;
use App\Modules\Company\Services\Payments\Directors\RebuildModulesDirector;
use App\Modules\Company\Services\Payments\ModuleService;
use App\Modules\Company\Services\Payments\Builders\ModuleBuilderFactory;
use App\Modules\Company\Services\Payments\Validator\ModuleValidatorFactory;
use App\Modules\Company\Services\PaymentService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Collection;

class ModuleController extends Controller
{
    /**
     * Current external modules.
     *
     * @param Guard $auth
     * @param ModuleService $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function current(Guard $auth, ModuleService $service)
    {
        $modules = $service->getCurrentActiveModules($auth);
        foreach ($modules as $module) {
            $module->has_active_subscription = $module->subscription_id && $module->subscription->active;
        }

        return ApiResponse::responseOk($modules);
    }

    /**
     * Available module mods for buy.
     *
     * @param Guard $auth
     * @param Module $module
     * @param ModuleService $service
     * @param ModuleValidatorFactory $validatorFactory
     * @param ModuleBuilderFactory $builderFactory
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function available(Guard $auth, Module $module, ModuleService $service, ModuleValidatorFactory $validatorFactory, ModuleBuilderFactory $builderFactory)
    {
        $modules = $service->getExternalModules($module);

        $modules_available = $service->getBuiltModules($modules, $auth, $validatorFactory, $builderFactory);

        return ApiResponse::transResponseOk($modules_available, 200, [
            ModuleMod::class => ModuleModWithoutModule::class,
            ModPrice::class => ModPriceWitchChecksum::class,
        ]);
    }

    /**
     * Buy module.
     *
     * @param ModuleStore $request
     * @param Guard $auth
     * @param ModuleBuilderFactory $builderFactory
     * @param CompanyModuleUpdater $updater
     * @param PaymentService $paymentService
     * @param Payment $payment
     * @param ModuleService $moduleService
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(
        ModuleStore $request,
        Guard $auth,
        ModuleBuilderFactory $builderFactory,
        CompanyModuleUpdater $updater,
        PaymentService $paymentService,
        Payment $payment,
        ModuleService $moduleService
    ) {
        $company = $auth->user()->selectedCompany();

        $params = $request->only(['days', 'is_test', 'checksum']);

        $director = new RebuildModulesDirector($builderFactory->create($request->input('mod_price_id'), $params));
        $module = $director->build($company);
        if (is_array($module)) {
            return ApiResponse::responseError(ErrorCode::PACKAGE_DATA_CONSISTENCY_ERROR, 409);
        }
        $mod_price = $module->mods[0]->modPrices[0];
        $modules = new Collection([$module]);
        $vat = (int) ceil($mod_price->price * 23 / 123);
        $transaction = $moduleService->moduleStaffBeforeRebuild($updater, $company, $payment, $modules, $paymentService, $mod_price->price, $vat, $params);

        return ApiResponse::responseOk($transaction->load('payments'));
    }

    /**
     * Remove extend module.
     *
     * @param $id
     * @param Guard $auth
     * @param CompanyModuleUpdater $updater
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id, Guard $auth, CompanyModuleUpdater $updater)
    {
        $company = $auth->user()->selectedCompany();
        $company_module = $company->companyModules()->where('module_id', $id)->first();
        if (! $company_module || $company_module->package_id ||
            ($company_module->subscription_id && $company_module->subscription->active)) {
            return ApiResponse::responseError(ErrorCode::RESOURCE_NOT_FOUND, 404);
        }

        $updater->setCompany($company);
        $updater->changeToDefault($company_module);

        return ApiResponse::responseOk([], 204);
    }

    /**
     * Limits with current usage.
     *
     * @param Guard $auth
     * @return \Illuminate\Http\JsonResponse
     */
    public function limits(Guard $auth)
    {
        $selected_company = $auth->user()->selectedCompany();

        $volume = 0;

        foreach ($selected_company->projects as $project) {
            $volume += $project->files()->sum('size');
        }

        $data = [
            ModuleType::PROJECTS_DISC_VOLUME => [
                'current' => $volume / 1024 / 1024 / 1024, //to GB
                'max' => $selected_company->appSettings(ModuleType::PROJECTS_DISC_VOLUME),
            ],
            ModuleType::GENERAL_MULTIPLE_USERS => [
                'current' => $selected_company->usersCompany()->where('status', UserCompanyStatus::APPROVED)->count(),
                'max' => $selected_company->appSettings(ModuleType::GENERAL_MULTIPLE_USERS),
            ],
            ModuleType::PROJECTS_MULTIPLE_PROJECTS => [
                'current' => $selected_company->projects()->count(),
                'max' => $selected_company->appSettings(ModuleType::PROJECTS_MULTIPLE_PROJECTS),
            ],
        ];

        return ApiResponse::responseOk($data);
    }
}
