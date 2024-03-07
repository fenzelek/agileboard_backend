<?php

namespace App\Modules\SaleInvoice\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Modules\SaleInvoice\Services\Invoice as ServiceInvoice;
use Illuminate\Http\JsonResponse;
use App\Modules\SaleInvoice\Http\Requests\InvoiceCorrectionType as RequestInvoiceCorrectionType;

class InvoiceCorrectionTypeController extends Controller
{
    /**
     * Display a listing of the correction types.
     *
     * @return JsonResponse
     */
    public function index(RequestInvoiceCorrectionType $request, ServiceInvoice $service)
    {
        return ApiResponse::responseOk($service->allowCorrectionTypes($request, auth()->user()));
    }
}
