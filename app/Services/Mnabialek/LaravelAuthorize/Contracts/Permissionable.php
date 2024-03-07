<?php

namespace App\Services\Mnabialek\LaravelAuthorize\Contracts;

interface Permissionable
{
    /**
     * Verify if resource has all given permissions (alias for canAll).
     *
     * @param Roleable $resource
     * @param string|array $permissions
     *
     * @return bool
     */
    public function can(Roleable $resource, $permissions);

    /**
     * Verify if resource has all given permissions.
     *
     * @param Roleable $resource
     * @param string|array $permissions
     *
     * @return bool
     */
    public function canAll(Roleable $resource, $permissions);

    /**
     * Verify if resource has any given permission.
     *
     * @param Roleable $resource
     * @param string|array $permissions
     *
     * @return bool
     */
    public function canAny(Roleable $resource, $permissions);

    /**
     * Get resource permissions.
     *
     * @param Roleable $resource
     *
     * @return array
     */
    public function getPermissions(Roleable $resource);
}
