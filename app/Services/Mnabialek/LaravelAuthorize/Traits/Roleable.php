<?php

namespace App\Services\Mnabialek\LaravelAuthorize\Traits;

trait Roleable
{
    /**
     * Verify whether object has assigned given role (or any of given roles if
     * array or roles given).
     *
     * @param string|array $roles
     *
     * @return bool
     */
    public function hasRole($roles)
    {
        $roles = (array) $roles;
        $userRoles = (array) $this->getRoles();

        foreach ($roles as $role) {
            if (in_array($role, $userRoles)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get roles names assigned to object.
     *
     * @return array
     */
    public function getRoles()
    {
        return [$this->role];
    }
}
