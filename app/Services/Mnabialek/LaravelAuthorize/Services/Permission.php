<?php

namespace App\Services\Mnabialek\LaravelAuthorize\Services;

use Illuminate\Contracts\Config\Repository as Config;
use Psr\Log\LoggerInterface as Log;
use App\Services\Mnabialek\LaravelAuthorize\Contracts\Permissionable;
use App\Services\Mnabialek\LaravelAuthorize\Contracts\PermissionHandler;
use App\Services\Mnabialek\LaravelAuthorize\Contracts\Roleable;

class Permission implements Permissionable
{
    /**
     * @var PermissionHandler
     */
    protected $handler;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Log
     */
    protected $log;

    /**
     * PermissionService constructor.
     *
     * @param PermissionHandler $handler
     * @param Log $log
     * @param Config $config
     */
    public function __construct(
        PermissionHandler $handler,
        Log $log,
        Config $config
    ) {
        $this->handler = $handler;
        $this->config = $config;
        $this->log = $log;
    }

    /**
     * Verify if resource has all given permissions (alias for canAll).
     *
     * @param Roleable|null $resource
     * @param string|array $permissions
     *
     * @return bool
     */
    public function can(Roleable $resource = null, $permissions)
    {
        return $this->canAll($resource, $permissions);
    }

    /**
     * Verify if resource has all given permissions.
     *
     * @param Roleable|null $resource
     * @param string|array $permissions
     *
     * @return bool
     */
    public function canAll(Roleable $resource = null, $permissions)
    {
        $permissions = (array) $permissions;

        $resourcePermissions = $this->getPermissions($resource);

        foreach ($permissions as $permission) {
            if (! in_array($permission, $resourcePermissions)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verify if resource has any given permission.
     *
     * @param Roleable|null $resource
     * @param string|array $permissions
     *
     * @return bool
     */
    public function canAny(Roleable $resource = null, $permissions)
    {
        $permissions = (array) $permissions;

        $resourcePermissions = $this->getPermissions($resource);

        foreach ($permissions as $permission) {
            if (in_array($permission, $resourcePermissions)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get resource permissions.
     *
     * @param Roleable|null $resource
     *
     * @return array
     */
    public function getPermissions(Roleable $resource = null)
    {
        return $this->handler->getPermissions($resource);
    }
}
