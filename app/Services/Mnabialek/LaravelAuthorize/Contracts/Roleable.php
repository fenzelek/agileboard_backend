<?php

namespace App\Services\Mnabialek\LaravelAuthorize\Contracts;

interface Roleable
{
    /**
     * Verify whether object has assigned given role (or any of given roles if
     * array or roles given).
     *
     * @param string|array $roles
     *
     * @return bool
     */
    public function hasRole($roles);

    /**
     * Get names of roles assigned to object.
     *
     * @return array
     */
    public function getRoles();
}
