<?php

namespace App\Modules\User\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Helpers\ErrorCode;
use App\Http\Controllers\Controller;
use App\Models\Db\UserShortToken;
use App\Modules\User\Http\Requests\AuthLogin;
use App\Modules\User\Http\Requests\QuickTokenLogin;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\JWTAuth;

class AuthController extends Controller
{
    /**
     * Log in user.
     *
     * @param AuthLogin $request
     *
     * @param JWTAuth $auth
     * @param Guard $guard
     *
     * @return Response
     */
    public function login(AuthLogin $request, JWTAuth $auth, Guard $guard)
    {
        // we allow to log in only users that are not deleted
        $credentials = array_merge($request->only('email', 'password'), ['deleted' => 0]);

        // invalid user
        if (! $guard->attempt($credentials)) {
            return ApiResponse::responseError(ErrorCode::AUTH_INVALID_LOGIN_DATA, 401);
        }

        // get user
        $user = $guard->user();

        if (! $user->isActivated()) {
            $guard->logout();

            return ApiResponse::responseError(ErrorCode::AUTH_NOT_ACTIVATED, 401);
        }

        // create user token
        try {
            $token = $auth->fromUser($user);
        } catch (JWTException $e) {
            return ApiResponse::responseError(ErrorCode::AUTH_CANNOT_CREATE_TOKEN, 500);
        }

        return ApiResponse::responseOk(['token' => $token], 201);
    }

    /**
     * Log out user.
     *
     * @param JWTAuth $auth
     *
     * @return Response
     */
    public function logout(JWTAuth $auth)
    {
        $auth->invalidate();

        return ApiResponse::responseOk([], 204);
    }

    /**
     * Converts API token into short user token that can be used in next request to get valid JWT
     * token.
     *
     * @param Guard $guard
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function apiToken(Guard $guard)
    {
        $short_token = UserShortToken::create([
            'user_id' => $guard->user()->id,
            'token' => str_random(mt_rand(100, 150)),
            'expires_at' => Carbon::now()->addMinutes(2),
        ]);

        return ApiResponse::responseOk($short_token, 201);
    }

    /**
     * Login via quick token.
     * 
     * @param QuickTokenLogin $request
     * @param JWTAuth $auth
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function loginViaQuickToken(QuickTokenLogin $request, JWTAuth $auth)
    {
        /** @var UserShortToken $user_short_token */
        $user_short_token = UserShortToken::fromQuickToken($request->input('token'));
        if (! $user_short_token) {
            return ApiResponse::responseError(ErrorCode::RESOURCE_NOT_FOUND, 404);
        }
        if ($user_short_token->isExpired()) {
            $user_short_token->delete();

            return ApiResponse::responseError(ErrorCode::RESOURCE_NOT_FOUND, 404);
        }

        try {
            $token = $auth->fromUser($user_short_token->user);
        } catch (JWTException $e) {
            return ApiResponse::responseError(ErrorCode::AUTH_CANNOT_CREATE_TOKEN, 500);
        }

        $user_short_token->delete();

        return ApiResponse::responseOk(['token' => $token], 201);
    }
}
