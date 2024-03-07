<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
//        \App\Http\Middleware\Cors::class, //cors is in htaccess
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
        ],

        // for guests only - login, password remind
        'api_guest' => [
            'throttle:15,1',
            'bindings',
            'guest',
        ],

        'api_user' => [
            'throttle:240,1',
            'bindings',
            'auth',
            'refresh.token',
        ],

        // when logout (without token refresh and permission checking)
        'api_logout' => [
            'throttle:120,1',
            'bindings',
            'auth',
        ],

        'api_user' => [
            'throttle:240,1',
            'bindings',
            'auth',
            'refresh.token',
        ],

        // standard api authorized user with permission checking
        'api_authorized' => [
            'throttle:240,1',
            'bindings',
            'auth',
            'refresh.token',
            'authorize',
        ],
        // standard api authorized user with permission checking
        'api_authorized_in_project' => [
            'throttle:240,1',
            'bindings',
            'auth',
            'auth.project',
            'refresh.token',
            'authorize',
        ],

        // standard api authorized user with permission checking
        'external_api_authorized' => [
            'throttle:240,1',
            'bindings',
            'external_auth',
            'authorize',
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,

        // user module middlewares
        'auth' => \App\Modules\User\Http\Middleware\Authenticate::class,
        'external_auth' => \App\Modules\User\Http\Middleware\ApiTokenAuthenticate::class,
        'guest' => \App\Modules\User\Http\Middleware\VerifyIfAuthenticated::class,
        'throttle' => \App\Modules\User\Http\Middleware\ThrottleRequests::class,
        'refresh.token' => \App\Modules\User\Http\Middleware\RefreshToken::class,

        'authorize' => \App\Http\Middleware\Authorize::class,
        'auth.project' => \App\Modules\Project\Http\Middleware\Authenticate::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'log_errors' => \App\Modules\SaleOther\Middleware\LogErrors::class,
        'log_http' => \Spatie\HttpLogger\Middlewares\HttpLogger::class,
    ];
}
