<?php

namespace App\Modules\Project\Http\Middleware;

use App\Helpers\ApiResponse;
use App\Helpers\ErrorCode;
use App\Models\Db\User;
use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Container\Container;

class Authenticate
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
     * Authenticate constructor.
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
        /** @var User $user */
        $user = $this->guard->user();

        // this should not happen (standard authentication should be used before this) but just in
        // case we make sure we have here valid user
        if (! $user || $user->isDeleted() || ! $user->isActivated()) {
            return ApiResponse::responseError(ErrorCode::AUTH_USER_NOT_FOUND, 404);
        }

        // if we are operating in selected project, we set selected project for user
        // to calculate user role in valid way
        if ($project = $request->route('project')) {
            /** @var \App\Models\Db\Project $project */
            $user->setSelectedProject($project);
        }

        return $next($request);
    }
}
