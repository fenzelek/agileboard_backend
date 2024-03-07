<?php

namespace App\Modules\SaleOther\Http\Controllers;

use App\Filters\ErrorLogsFilter;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Db\ErrorLog;
use App\Modules\SaleOther\Http\Requests\ErrorLogIndex;
use App\Services\Paginator;
use Illuminate\Contracts\Auth\Guard;

class ErrorLogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param ErrorLogIndex $request
     * @param Paginator $paginator
     * @param Guard $auth
     * @param ErrorLogsFilter $error_logs_filter
     *
     * @return ApiResponse
     */
    public function index(
        ErrorLogIndex $request,
        Paginator $paginator,
        Guard $auth,
        ErrorLogsFilter $error_logs_filter
    ) {
        $errors = ErrorLog::inCompany($auth->user())->with('user')->filtered($error_logs_filter);
        $errors = $paginator->get($errors, 'errors.index');

        return ApiResponse::responseOk($errors, 200);
    }

    public function destroy(Guard $auth)
    {
        ErrorLog::inCompany($auth->user())->delete();

        return ApiResponse::responseOk([], 204);
    }
}
