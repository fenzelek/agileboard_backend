<?php

namespace App\Modules\Integration\Http\Controllers;

use App\Filters\TimeTrackingUserFilter;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\TimeTracking\User as TimeTrackingUserTransformer;
use App\Models\Db\Integration\TimeTracking\User;
use App\Services\Paginator;
use App\Modules\Integration\Http\Requests\TimeTracking\User as UserRequest;

class TimeTrackingUserController extends Controller
{
    /**
     * Get list of users.
     *
     * @param UserRequest $request
     * @param Paginator $paginator
     * @param TimeTrackingUserFilter $filter
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(UserRequest $request, Paginator $paginator, TimeTrackingUserFilter $filter)
    {
        return ApiResponse::transResponseOk($paginator->get(
            User::filtered($filter),
            'time-tracking-user.index'
        ), 200, [
            User::class => TimeTrackingUserTransformer::class,
        ]);
    }
}
