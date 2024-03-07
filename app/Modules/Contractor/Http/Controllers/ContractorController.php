<?php

namespace App\Modules\Contractor\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Contractor\Http\Requests\ContractorStoreUpdate;
use App\Modules\Contractor\Http\Requests\ContractorIndex;
use App\Modules\Contractor\Services\Contractor as ContractorService;
use App\Services\Paginator;
use Illuminate\Http\JsonResponse;

class ContractorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Paginator $paginator
     * @param ContractorIndex $request
     * @param ContractorService $service
     * @return JsonResponse
     */
    public function index(Paginator $paginator, ContractorIndex $request, ContractorService $service)
    {
        return ApiResponse::responseOk($service->index($paginator, auth()->user(), $request));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param ContractorStoreUpdate $request
     * @param ContractorService $service
     * @return JsonResponse
     */
    public function store(ContractorStoreUpdate $request, ContractorService $service)
    {
        $company = $service->create(auth()->user(), $request);

        return ApiResponse::responseOk($company, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @param ContractorService $service
     * @return JsonResponse
     */
    public function show(ContractorService $service, $id)
    {
        return ApiResponse::responseOk($service->show(auth()->user(), $id));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param ContractorStoreUpdate $request
     * @param ContractorService $service
     * @param $id
     * @return JsonResponse
     */
    public function update(ContractorStoreUpdate $request, ContractorService $service, $id)
    {
        $company = $service->update(auth()->user(), $request, $id);

        return ApiResponse::responseOk($company, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param ContractorService $service
     * @param $id
     * @return JsonResponse
     */
    public function destroy(ContractorService $service, $id)
    {
        $service->destroy(auth()->user(), $id);

        return ApiResponse::responseOk();
    }
}
