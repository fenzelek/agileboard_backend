<?php

namespace App\Modules\Company\Http\Controllers;

use App\Filters\CompanyServiceFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Db\CompanyService;
use App\Modules\Company\Http\Requests\CompanyService as CompanyServiceRequest;
use App\Services\Paginator;
use Illuminate\Contracts\Auth\Guard;
use App\Modules\Company\Services\CompanyService as ServiceCompanyService;

class CompanyServiceController extends Controller
{
    /**
     * Display a listing of the company services.
     *
     * @param Request $request
     * @param Paginator $paginator
     * @param Guard $auth
     *
     * @return JsonResponse
     */
    public function index(Request $request, Paginator $paginator, Guard $auth, CompanyServiceFilter $company_service_filter)
    {
        $company_service_query = CompanyService::inCompany($auth->user());

        $company_services = $paginator->get($company_service_query->filtered($company_service_filter)->with('serviceUnit'), 'company-service.index');

        return ApiResponse::responseOk($company_services);
    }

    /**
     * Store new company services.
     *
     * @param CompanyServiceRequest $request
     * @param ServiceCompanyService $service
     * @param Guard $auth
     *
     * @return JsonResponse
     */
    public function store(CompanyServiceRequest $request, ServiceCompanyService $service, Guard $auth)
    {
        $company_service = $service->create($request, $auth);

        return ApiResponse::responseOk($company_service, 201);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param CompanyServiceRequest $request
     * @param ServiceCompanyService $service
     * @param int $id
     *
     * @return JsonResponse
     */
    public function update(CompanyServiceRequest $request, ServiceCompanyService $service, Guard $auth, $id)
    {
        $company_service = CompanyService::findOrFail($id);

        $service->update($company_service, $request, $auth);

        return ApiResponse::responseOk($company_service->fresh(), 200);
    }

    /**
     * Show one company service.
     *
     * @param Request $request
     * @param Guard $auth
     * @param int $id
     *
     * @return JsonResponse
     */
    public function show(Request $request, Guard $auth, $id)
    {
        return ApiResponse::responseOk(CompanyService::inCompany($auth->user())->with('serviceUnit')
            ->findOrFail($id), 200);
    }
}
