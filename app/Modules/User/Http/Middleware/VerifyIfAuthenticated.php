<?php

namespace App\Modules\User\Http\Middleware;

use App\Helpers\ApiResponse;
use App\Helpers\ErrorCode;
use Closure;
use Tymon\JWTAuth\JWTAuth;

class VerifyIfAuthenticated
{
    /**
     * @var JWTAuth
     */
    protected $tokenAuth;

    /**
     * Authenticate constructor.
     *
     * @param JWTAuth $tokenAuth
     */
    public function __construct(JWTAuth $tokenAuth)
    {
        $this->tokenAuth = $tokenAuth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @param  string|null $guard
     *
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $user = null;

        try {
            $user = $this->tokenAuth->setRequest($request)->parseToken()->authenticate();
        } catch (\Exception $e) {
            // we don't care about exceptions in this place
        }

        // if user is not deleted it means they are already logged
        if ($user && ! $user->isDeleted() && $user->isActivated()) {
            return ApiResponse::responseError(ErrorCode::AUTH_ALREADY_LOGGED, 403);
        }

        return $next($request);
    }
}
