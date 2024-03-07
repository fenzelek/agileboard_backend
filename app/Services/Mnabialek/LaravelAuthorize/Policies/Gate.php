<?php

namespace App\Services\Mnabialek\LaravelAuthorize\Policies;

class Gate extends \Illuminate\Auth\Access\Gate
{
    /**
     * Get the raw result for the given ability for the current user.
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return mixed
     */
    public function raw($ability, $arguments = [])
    {
        // here we don't fail in case user is not logged, we want to allow
        // verify unauthorized user permissions
        $user = $this->resolveUser();

        $arguments = array_wrap($arguments);

        // First we will call the "before" callbacks for the Gate. If any of these give
        // back a non-null response, we will immediately return that result in order
        // to let the developers override all checks for some authorization cases.
        $result = $this->callBeforeCallbacks(
            $user,
            $ability,
            $arguments
        );

        if (null === $result) {
            $result = $this->callAuthCallback($user, $ability, $arguments);
        }

        // After calling the authorization callback, we will call the "after" callbacks
        // that are registered with the Gate, which allows a developer to do logging
        // if that is required for this application. Then we'll return the result.
        $this->callAfterCallbacks(
            $user,
            $ability,
            $arguments,
            $result
        );

        return $result;
    }

    /**
     * Resolve the callback for a policy check.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  string $ability
     * @param  array $arguments
     * @param  mixed $policy
     *
     * @return callable
     */
    protected function resolvePolicyCallback($user, $ability, array $arguments, $policy)
    {
        return function () use ($user, $ability, $arguments, $policy) {
            // This callback will be responsible for calling the policy's before method and
            // running this policy method if necessary. This is used to when objects are
            // mapped to policy objects in the user's configurations or on this class.
            $result = $this->callPolicyBefore(
                $policy,
                $user,
                $ability,
                $this->getPolicyArguments($arguments)
            );

            // When we receive a non-null result from this before method, we will return it
            // as the "final" results. This will allow developers to override the checks
            // in this policy to return the result for all rules defined in the class.
            if (null !== $result) {
                return $result;
            }

            $ability = $this->formatAbilityToMethod($ability);
            $arguments = $this->getPolicyArguments($arguments);

            return is_callable([$policy, $ability])
                ? $policy->{$ability}($user, ...$arguments)
                : false;
        };
    }

    /**
     * Get policy arguments.
     *
     * @param array $arguments
     *
     * @return array
     */
    protected function getPolicyArguments(array $arguments)
    {
        // if 1st argument is controller name we want to exclude it from
        // arguments we will apply to policy methods (we won't need it for
        // anything)
        if ($arguments && $this->isController($arguments[0])) {
            $arguments = array_splice($arguments, 1);
        }

        return $arguments;
    }

    /**
     * Verify whether given resource is controller.
     *
     * @param string $resourceName
     *
     * @return bool
     */
    protected function isController($resourceName)
    {
        return (mb_strpos($resourceName, 'Controller') !== false);
    }
}
