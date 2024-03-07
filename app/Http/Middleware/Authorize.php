<?php

namespace App\Http\Middleware;

use App\Helpers\ApiResponse;
use App\Helpers\ErrorCode;
use App\Services\Mnabialek\LaravelAuthorize\Middleware\Authorize as BaseAuthorize;

class Authorize extends BaseAuthorize
{
    /**
     * @inheritdoc
     */
    protected function errorResponse($request)
    {
        return ApiResponse::responseError(ErrorCode::NO_PERMISSION, 401);
    }

    /**
     * @inheritdoc
     */
    protected function reportUnauthorizedAttempt(
        $controller,
        $action,
        $request,
        $bindings
    ) {
        // you might want to log unauthorized attempts somewhere
    }
}
