<?php

namespace App\Modules\User\Http\Middleware;

use App\Helpers\ApiResponse;
use App\Helpers\ErrorCode;
use App\Models\Db\User;
use App\Modules\Company\Exceptions\ExpiredToken;
use App\Modules\Company\Exceptions\InvalidToken;
use App\Modules\Company\Exceptions\NoTokenFound;
use App\Modules\Company\Services\Token;
use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Container\Container;

class ApiTokenAuthenticate
{
    /**
     * @var Container
     */
    protected $app;

    /**
     * @var Guard
     */
    protected $guard;

    /**
     * ApiTokenAuthenticate constructor.
     *
     * @param Container $app
     * @param Guard $guard
     */
    public function __construct(Container $app, Guard $guard)
    {
        $this->app = $app;
        $this->guard = $guard;
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
        // first verify if token is set in header
        if (! $request->hasHeader('Authorization-Api-Token')) {
            return ApiResponse::responseError(ErrorCode::AUTH_EXTERNAL_API_MISSING_TOKEN, 401);
        }

        try {
            /** @var Token $token_service */
            $token_service = $this->app->make(Token::class);
            $token = $token_service->decode($request->header('Authorization-Api-Token'));
        } catch (InvalidToken $e) {
            return ApiResponse::responseError(ErrorCode::AUTH_EXTERNAL_INVALID_TOKEN, 401);
        } catch (NoTokenFound $e) {
            return ApiResponse::responseError(ErrorCode::AUTH_EXTERNAL_INVALID_TOKEN, 401);
        } catch (ExpiredToken $e) {
            return ApiResponse::responseError(ErrorCode::AUTH_EXTERNAL_EXPIRED_TOKEN, 401);
        }

        // let's now verify token domain and ip constraints
        if (! $token->validForServer($request->getHost(), $request->ip())) {
            return ApiResponse::responseError(ErrorCode::AUTH_EXTERNAL_INVALID_TOKEN, 401);
        }

        /** @var User $user */
        $user = $token->user;

        // we allow authenticate only users that are not deleted
        if (! $user || $user->isDeleted()) {
            return ApiResponse::responseError(ErrorCode::AUTH_USER_NOT_FOUND, 404);
        }

        // we don't allow to authenticate not activated users
        if (! $user->isActivated()) {
            return ApiResponse::responseError(ErrorCode::AUTH_NOT_ACTIVATED, 409);
        }

        // now we log in user
        $this->guard->setUser($user);

        // first we set user system role
        $user->setSystemRole();
        // and finally we set selected company for user to calculate user role in valid way
        $user->setSelectedCompany($token->company_id, $token->role);

        return $next($request);
    }
}
