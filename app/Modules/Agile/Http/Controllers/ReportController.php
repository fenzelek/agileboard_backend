<?php

namespace App\Modules\Agile\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Agile\Http\Requests\ReportDaily;
use App\Modules\Agile\Services\Report;
use App\Services\Paginator;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;

class ReportController extends Controller
{
    public function daily(ReportDaily $request, Report $report_service, Paginator $paginator, Guard $guard)
    {
        $date_from = Carbon::parse($request->input('date_from'));
        $date_to = Carbon::parse($request->input('date_to'));
        $project_id = $request->input('project_id');

        $daily_report_query = $report_service->getDaily($date_from, $date_to, $guard->user(), $project_id);

        return ApiResponse::responseOk(
            $paginator->get(
                $daily_report_query,
                'reports.daily'
            )
        );
    }

    public function todo()
    {
        // TODO: Implement this method
    }
}
