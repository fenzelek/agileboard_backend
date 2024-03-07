<?php

namespace App\Modules\Gantt\Http\Controllers;

use Carbon\Carbon;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Gantt\Http\Requests\WorkloadParams;
use App\Modules\Gantt\Services\WorkloadService;

class WorkloadController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param WorkloadParams $request
     * @param WorkloadService $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(WorkloadParams $request, WorkloadService $service)
    {
        $chart_start_date = Carbon::parse($request->input('from'))->startOfWeek();

        //find end date for the chart which is displayed
        $chart_end_date = $chart_start_date->copy();
        $period = (int) $request->input('period', 6);
        $chart_end_date->addWeeks($period - 1)->endOfWeek();

        $company_id = auth()->user()->getSelectedCompanyId();

        $data = $service->prepare(clone($chart_start_date), clone($chart_end_date), $company_id);

        return ApiResponse::responseOk(
            $data,
            200,
            [
                'date_start' => $chart_start_date->format('Y-m-d'),
                'date_end' => $chart_end_date->format('Y-m-d'),
            ]
        );
    }
}
