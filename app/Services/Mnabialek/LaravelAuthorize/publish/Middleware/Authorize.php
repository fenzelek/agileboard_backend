<?php

namespace App\Services\Mnabialek\LaravelAuthorize\publish\Middleware;

use App\Services\Mnabialek\LaravelAuthorize\Middleware\Authorize as BaseAuthorize;

class Authorize extends BaseAuthorize
{
    /**
     * @inheritdoc
     */
    protected function errorResponse($request)
    {
        // you might want to customize here your response when user has no
        // permission
        return parent::errorResponse($request);
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
