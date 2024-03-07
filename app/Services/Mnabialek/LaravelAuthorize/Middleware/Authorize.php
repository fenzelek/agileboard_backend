<?php

namespace App\Services\Mnabialek\LaravelAuthorize\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Router;
use App\Services\Mnabialek\LaravelAuthorize\Contracts\Roleable;

class Authorize
{
    /**
     * @var Guard
     */
    protected $auth;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var Gate
     */
    protected $gate;

    /**
     * Authorize constructor.
     *
     * @param Guard $auth
     * @param Config $config
     * @param Router $router
     * @param Gate $gate
     */
    public function __construct(
        Guard $auth,
        Config $config,
        Router $router,
        Gate $gate
    ) {
        $this->auth = $auth;
        $this->config = $config;
        $this->router = $router;
        $this->gate = $gate;
    }

    /**
     * Handle an incoming request.
     *
     * @param  Request $request
     * @param  \Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        list($controller, $action) = $this->getControllerAndAction();
        $bindings = $this->getBindings();

        $authorized = false;

        /** @var Roleable $user */
        $user = $this->auth->user();

        $args = func_get_args();

        if (count($args) > 2) {
            // Role based authorization
            $roles = $this->getAllowedRoles(array_slice($args, 2));

            if (! $user && in_array($this->getGuestRole(), $roles)) {
                $authorized = true;
            } elseif ($user && $user->hasRole($roles)) {
                $authorized = true;
            }
        } else {
            // Permission based authorization
            if ($this->gate->forUser($user)->check($action, array_merge([$controller], $bindings))
            ) {
                $authorized = true;
            }
        }

        // if user is not authorized, we will return errror response
        if (! $authorized) {
            $this->reportUnauthorizedAttempt(
                $controller,
                $action,
                $request,
                $bindings
            );

            return $this->errorResponse($request);
        }

        return $next($request);
    }

    /**
     * Get guest role name.
     *
     * @return string
     */
    protected function getGuestRole()
    {
        return $this->config->get('authorize.guest_role_name');
    }

    /**
     * Get allowed roles for current route.
     *
     * @param array $roles
     *
     * @return array
     */
    protected function getAllowedRoles(array $roles)
    {
        // we add here super roles so we don't need to add admin roles each time
        // we add other roles
        return array_values(array_unique(array_merge(
            $roles,
            $this->config->get('authorize.super_roles')
        )));
    }

    /**
     * Report unauthorized attempt.
     *
     * @param string $controller
     * @param string $action
     * @param Request $request
     * @param array $bindings
     */
    protected function reportUnauthorizedAttempt(
        $controller,
        $action,
        $request,
        $bindings
    ) {
        // by default we don't log anything
    }

    /**
     * Get error response (for not authorized user).
     *
     * @param Request $request
     *
     * @return Response
     */
    protected function errorResponse($request)
    {
        if ($request->ajax()) {
            return response('Unauthorized.', 401);
        }

        return response(view('errors.401'), 401);
    }

    /**
     * Get current route bindings.
     *
     * @return array
     */
    protected function getBindings()
    {
        return array_values($this->router->getCurrentRoute()->parameters());
    }

    /**
     * Get controller and action from current route.
     *
     * @return array
     */
    protected function getControllerAndAction()
    {
        $name = $this->router->getCurrentRoute()->getActionName();

        // don't allow closures for routes protected by this middleware
        if (! str_contains($name, '@')) {
            throw new \LogicException('Closures for routes not allowed in this application');
        }

        return explode('@', $name);
    }
}
