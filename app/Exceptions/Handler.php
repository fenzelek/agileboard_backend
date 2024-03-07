<?php

namespace App\Exceptions;

use App\Helpers\ApiResponse;
use App\Helpers\ErrorCode;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PDOException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [

    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Throwable $e
     *
     * @return void
     */
    public function report(Throwable $e)
    {
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Throwable $e
     *
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable $e)
    {
        $exception_class = get_class($e);

        // if debugging is set to true, we will return standard error response to easier detect
        // error and solve it by developer (except validation errors)
        if (config('app.debug', false) && $exception_class != ValidationException::class) {
            return parent::render($request, $e);
        }

        // otherwise we will return custom API response
        $data = [];

        switch ($exception_class) {
            case ValidationException::class:
                $errorCode = ErrorCode::VALIDATION_FAILED;
                $responseCode = 422;
                $data = $e->errors();
                break;
            case ModelNotFoundException::class:
            case FileException::class:
                $errorCode = ErrorCode::RESOURCE_NOT_FOUND;
                $responseCode = 404;
                break;
            case NotFoundHttpException::class:
            case MethodNotAllowedHttpException::class:
                $errorCode = ErrorCode::NOT_FOUND;
                $responseCode = 404;
                break;
            case PDOException::class:
                $errorCode = ErrorCode::DATABASE_ERROR;
                $responseCode = 500;
                break;
            default:
                $errorCode = ErrorCode::API_ERROR;
                $responseCode = 500;
        }

        return ApiResponse::responseError($errorCode, $responseCode, $data);
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Illuminate\Auth\AuthenticationException $exception
     *
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        // @todo should be altered to API format
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        return redirect()->guest('login');
    }
}
