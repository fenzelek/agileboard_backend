<?php

namespace App\Modules\SaleOther\Services;

use App\Filters\ErrorLogsFilter;
use App\Models\Db\ErrorLog;
use App\Models\Db\User;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogErrors
{
    /**
     * Array of http response code which should be logged in DB.
     *
     * @var array
     */
    protected $loggedErrorCodes = [
        422,
        420,
    ];

    /**
     * @var ErrorLog
     */
    protected $error_logs;

    /**
     * @var Guard
     */
    protected $auth;

    /**
     * LogErrors constructor.
     *
     * @param ErrorLog $error_logs
     * @param Guard $auth
     */
    public function __construct(
        ErrorLog $error_logs,
        Guard $auth,
        ErrorLogsFilter $error_logs_filter
    ) {
        $this->error_logs = $error_logs;
        $this->auth = $auth;
        $this->error_logs_filter = $error_logs_filter;
    }

    /**
     * Check if error with given code should be logged.
     *
     * @param $error_code int
     *
     * @return bool
     */
    public function loggable($error_code)
    {
        return in_array($error_code, $this->loggedErrorCodes);
    }

    /**
     * Logging error and other useful information to DB.
     *
     * @param Request $request
     * @param JsonResponse $response
     * @param User $user
     */
    public function create(Request $request, JsonResponse $response, User $user)
    {
        ErrorLog::create([
            'company_id' => $user->getSelectedCompanyId(),
            'user_id' => $user->id,
            'transaction_number' => $request->input('transaction_number'),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'request' => json_encode($request->input()),
            'status_code' => $response->status(),
            'response' => $response->content(),
            'request_date' => Carbon::now(),
        ]);
    }
}
