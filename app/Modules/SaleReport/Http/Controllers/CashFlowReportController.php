<?php

namespace App\Modules\SaleReport\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Modules\CashFlow\Services\CashFlow as ServiceCashFlow;
use App\Modules\SaleReport\Http\Requests\CashFlowReport;
use Illuminate\Http\JsonResponse;

class CashFlowReportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param CashFlowReport $request
     * @param ServiceCashFlow $service
     *
     * @return JsonResponse
     */
    public function index(CashFlowReport $request, ServiceCashFlow $service)
    {
        $cash_flows_report_summary = $service->filterCashFlowReportSummary($request, auth()->user());

        return ApiResponse::responseOk($cash_flows_report_summary);
    }
}
