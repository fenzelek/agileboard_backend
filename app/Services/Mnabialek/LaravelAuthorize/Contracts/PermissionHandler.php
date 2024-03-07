<?php

namespace App\Services\Mnabialek\LaravelAuthorize\Contracts;

interface PermissionHandler
{
    /**
     * Get list of all permissions that resource has. If null resource given
     * it will get list of permissions for default role for null resource.
     *
     * @param Roleable|null $resource
     *
     * @return array
     */
    public function getPermissions(Roleable $resource = null);
}
