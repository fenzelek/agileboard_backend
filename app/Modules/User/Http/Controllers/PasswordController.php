<?php

namespace App\Modules\User\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Helpers\ErrorCode;
use App\Http\Controllers\Controller;
use App\Modules\User\Http\Requests\ResetPassword;
use App\Modules\User\Http\Requests\SendResetEmail;
use Password;

class PasswordController extends Controller
{
    /**
     * Constant representing an invalid password.
     *
     * @var string
     */
    const INVALID_PASSWORD = 'passwords.password';

    /**
     * Send a reset link to the given user.
     *
     * @param  SendResetEmail  $request
     * @return \Illuminate\Http\Response
     */
    public function sendResetLinkEmail(SendResetEmail $request)
    {
        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.

        $initial_language = config('app.locale');
        trans()->setLocale($request->input('language', 'en'));
        $response = Password::broker()->sendResetLink(
            array_merge($request->only('email'), ['deleted' => 0, 'activated' => 1])
        );
        trans()->setLocale($initial_language);

        if ($response === Password::RESET_LINK_SENT) {
            return ApiResponse::responseOk([], 201);
        }

        // If an error was returned by the password broker, we will get this message
        // translated so we can notify a user of the problem. We'll redirect back
        // to where the users came from so they can attempt this process again.
        return ApiResponse::responseError(ErrorCode::PASSWORD_NO_USER_FOUND, 404);
    }

    /**
     * Reset user password.
     *
     * @param ResetPassword $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function reset(ResetPassword $request)
    {
        $credentials = array_merge($request->only(
            'email',
            'password',
            'password_confirmation',
            'token'
        ), ['deleted' => 0, 'activated' => 1]);

        // try to change user password
        $response = Password::broker()
            ->reset($credentials, function ($user, $password) {
                $user->password = $password;
                $user->save();
            });

        // return valid response based on Password broker response
        switch ($response) {
            case Password::PASSWORD_RESET:
                return ApiResponse::responseOk();
            case self::INVALID_PASSWORD:
                return ApiResponse::responseError(
                    ErrorCode::PASSWORD_INVALID_PASSWORD,
                    422
                );
            case Password::INVALID_TOKEN:
                return ApiResponse::responseError(
                    ErrorCode::PASSWORD_INVALID_TOKEN,
                    422
                );
            case Password::INVALID_USER:
            default:
                return ApiResponse::responseError(
                    ErrorCode::PASSWORD_NO_USER_FOUND,
                    404
                );
        }
    }
}
