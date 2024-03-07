<?php

namespace App\Modules\Company\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Helpers\ErrorCode;
use App\Http\Controllers\Controller;
use App\Http\Resources\FullCompany;
use App\Http\Resources\FullCompanyWithPackage;
use App\Models\Db\Company;
use App\Modules\Company\Http\Requests\CompanySettingsUpdate;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\JsonResponse;
use App\Modules\Company\Http\Requests\Gus as GusRequest;
use App\Modules\Company\Services\Gus;
use App\Models\Db\CountryVatinPrefix;
use App\Modules\Company\Http\Requests\Company as CompanyRequest;
use App\Modules\Company\Http\Requests\CompanyUpdate as CompanyUpdateRequest;
use App\Modules\Company\Http\Requests\CompanyPaymentMethodUpdate as CompanyPaymentMethodUpdate;
use App\Modules\Company\Services\Company as CompanyService;
use Illuminate\Contracts\Auth\Guard;

class CompanyController extends Controller
{
    /**
     * @var FilesystemManager
     */
    protected $filesystem;

    /**
     * CompanyController constructor.
     *
     * @param FilesystemManager $filesystem
     */
    public function __construct(FilesystemManager $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param CompanyRequest $request
     * @param CompanyService $service
     *
     * @return JsonResponse
     */
    public function store(CompanyRequest $request, CompanyService $service)
    {
        if (! $service->canCreate()) {
            return ApiResponse::responseError(ErrorCode::COMPANY_CREATION_LIMIT, 422);
        }

        $saving_fields = ['name', 'vat_payer'];

        if ($request->vat_payer == false) {
            $saving_fields = array_merge($saving_fields, ['vat_release_reason_id', 'vat_release_reason_note']);
        }

        $company = $service->create($request->only($saving_fields));

        return ApiResponse::responseOk($company, 201);
    }

    /**
     * Update company fields.
     *
     * @param CompanyUpdateRequest $request
     * @param CompanyService $service
     *
     * @return JsonResponse
     */
    public function update(CompanyUpdateRequest $request, CompanyService $service)
    {
        if ($service->blockedVatPayerSetting(auth()->user(), $request)) {
            return ApiResponse::responseError(ErrorCode::COMPANY_BLOCKED_CHANGING_VAT_PAYER_SETTING, 421);
        }

        $company = $service->update(auth()->user(), $request);

        return ApiResponse::transResponseOk($company, 200, [Company::class => FullCompany::class]);
    }

    /**
     * Update company settings fields.
     *
     * @param CompanySettingsUpdate $request
     * @param CompanyService $service
     * @return JsonResponse
     * @throws \Exception
     */
    public function updateSettings(CompanySettingsUpdate $request, CompanyService $service)
    {
        $company = $service->updateSettings(auth()->user(), $request);

        return ApiResponse::transResponseOk($company, 200, [Company::class => FullCompany::class]);
    }

    /**
     * Get current user company.
     *
     * @return JsonResponse
     */
    public function showCurrent()
    {
        return ApiResponse::transResponseOk(
            Company::with('bankAccounts')->findOrFail(auth()->user()->getSelectedCompanyId()),
            200,
            [Company::class => FullCompanyWithPackage::class]
        );
    }

    /**
     * Retrieve all companies. Method Available only for super admin.
     *
     * @return JsonResponse
     */
    public function index()
    {
        return ApiResponse::transResponseOk(Company::with('bankAccounts')->orderBy('id', 'desc')->get(), 200, [
            Company::class => FullCompany::class,
        ]);
    }

    /**
     * Update default payment method for company.
     *
     * @param CompanyPaymentMethodUpdate $request
     * @param Guard $auth
     *
     * @return JsonResponse
     */
    public function updatePaymentMethod(CompanyPaymentMethodUpdate $request, Guard $auth)
    {
        Company::findOrFail($auth->user()->getSelectedCompanyId())->update([
            'default_payment_method_id' => $request->input('default_payment_method_id'),
            'editor_id' => $auth->user()->id,
        ]);

        return ApiResponse::responseOk([], 200);
    }

    /**
     * Get company logotype file.
     *
     * @param Guard $auth
     * @return JsonResponse
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function getLogotype(Guard $auth)
    {
        $company = Company::findOrFail($auth->user()->getSelectedCompanyId());
        if ($company->logotype) {
            $file = $this->filesystem->disk('logotypes')->get($company->logotype);
            $mimeType = $this->filesystem->disk('logotypes')->mimeType($company->logotype);

            return response($file, 200)->header('Content-Type', $mimeType);
        }

        return ApiResponse::responseOk([]);
    }

    /**
     * @param $id
     * @param Guard $auth
     * @return JsonResponse
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function getLogotypeSelectedCompany($id, Guard $auth)
    {
        $company = Company::whereHas('users', function ($q) use ($auth) {
            $q->where('user_id', $auth->user()->id);
        })->findOrFail($id);

        if ($company->logotype) {
            $file = $this->filesystem->disk('logotypes')->get($company->logotype);
            $mimeType = $this->filesystem->disk('logotypes')->mimeType($company->logotype);

            return response($file, 200)->header('Content-Type', $mimeType);
        }

        return ApiResponse::responseOk([]);
    }

    /**
     * Get company data from DB or GUS based on passed vatin.
     *
     * @param GusRequest $request
     * @param Gus $gus
     *
     * @return JsonResponse
     */
    public function getGusData(GusRequest $request, Gus $gus)
    {
        $gus_response = $gus->getDataByVatin($request->input('vatin'));
        if ($gus_response === false) {
            return ApiResponse::responseError(ErrorCode::GUS_TECHNICAL_PROBLEMS, 425);
        }

        return ApiResponse::responseOk($gus_response);
    }

    /**
     * Get list of vatin prefixes.
     *
     * @return JsonResponse
     */
    public function indexCountryVatinPrefixes()
    {
        return ApiResponse::responseOk(CountryVatinPrefix::all()->sortBy('id'));
    }
}
