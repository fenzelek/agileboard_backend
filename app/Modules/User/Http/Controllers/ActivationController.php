<?php

namespace App\Modules\User\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Helpers\ErrorCode;
use App\Http\Controllers\Controller;
use App\Models\Db\User;
use App\Modules\User\Events\ActivationTokenWasRequested;
use App\Modules\User\Events\UserWasActivated;
use App\Modules\User\Http\Requests\ActivateRequest;
use App\Modules\User\Http\Requests\ActivationResendRequest;
use DB;
use Tymon\JWTAuth\JWTAuth;
use Illuminate\Contracts\Events\Dispatcher as Event;

class ActivationController extends Controller
{
    /**
     * @var JWTAuth
     */
    protected $auth;

    /**
     * @var User
     */
    protected $user;

    /**
     * ActivationController constructor.
     *
     * @param JWTAuth $auth
     * @param User $user
     */
    public function __construct(JWTAuth $auth, User $user)
    {
        $this->auth = $auth;
        $this->user = $user;
    }

    /**
     * Activate user account.
     *
     * @param ActivateRequest $request
     * @param Event $event
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function activate(ActivateRequest $request, Event $event)
    {
        /** @var User $user */
        $user = $this->user->where('activate_hash', $request->input('activation_token'))->first();

        // no user or user was deleted
        if (! $user || $user->isDeleted()) {
            return ApiResponse::responseError(ErrorCode::ACTIVATION_INVALID_TOKEN_OR_USER, 404);
        }

        // user already activated
        if ($user->isActivated()) {
            return ApiResponse::responseError(ErrorCode::ACTIVATION_ALREADY_ACTIVATED, 409);
        }

        DB::transaction(function () use ($user, $event) {
            $user->activate();
            $event->dispatch(new UserWasActivated($user));
        });

        return ApiResponse::responseOk(
            ['token' => $this->auth->fromUser($user)],
            200
        );
    }

    /**
     * Resend activation token for user.
     *
     * @param ActivationResendRequest $request
     * @param Event $event
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function resend(ActivationResendRequest $request, Event $event)
    {
        /** @var User $user */
        $user = $this->user->where('email', $request->input('email'))->first();

        // no user or user was deleted
        if (! $user || $user->isDeleted()) {
            return ApiResponse::responseError(ErrorCode::AUTH_USER_NOT_FOUND, 404);
        }

        // user already activated
        if ($user->isActivated()) {
            return ApiResponse::responseError(ErrorCode::ACTIVATION_ALREADY_ACTIVATED, 409);
        }

        // dispatch event
        DB::transaction(function () use ($user, $event, $request) {
            $event->dispatch(new ActivationTokenWasRequested(
                $user,
                $request->input('url'),
                $request->input('language', 'en')
            ));
        });

        return ApiResponse::responseOk([], 200);
    }
}
