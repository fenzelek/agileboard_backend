<?php

namespace App\Services\Mnabialek\LaravelAuthorize\Policies;

use Illuminate\Contracts\Config\Repository as Config;
use Psr\Log\LoggerInterface as Log;
use Illuminate\Http\Request;
use App\Services\Mnabialek\LaravelAuthorize\Contracts\Permissionable;
use App\Services\Mnabialek\LaravelAuthorize\Contracts\Roleable;

class BasePolicyController
{
    /**
     * @var Permissionable
     */
    protected $permService;

    /**
     * @var ?string
     */
    protected $group;

    /**
     * @var Log
     */
    protected $log;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Request
     */
    protected $request;

    /**
     * BasePolicyController constructor.
     *
     * @param Log $log
     * @param Config $config
     * @param Permissionable $permService
     * @param Request $request
     */
    public function __construct(
        Log $log,
        Config $config,
        Permissionable $permService,
        Request $request
    ) {
        $this->permService = $permService;
        $this->log = $log;
        $this->config = $config;
        $this->request = $request;
    }

    /**
     * Base authorization verification method. In case non-null value is
     * returned this will indicate whether user has (or not) access for given
     * resource.
     *
     * @param Roleable|null $user
     * @param string $ability
     *
     * @return bool|null
     * @throws \Exception
     */
    public function before(Roleable $user = null, $ability)
    {
        // for super roles we will always allow everything no matter what
        // specific permissions are defined later
        $superRoles = $this->getSuperRoles();
        if ($superRoles && $user && $user->hasRole($superRoles)) {
            return true;
        }

        // verify if user has permission for this group and this ability
        $can =
            $this->permService->can($user, $this->getPermissionName($ability));

        // if user has no permission for this action, we don't need to do
        // anything more - user won't be able do run this action
        if (! $can) {
            return false;
        }

        // if he has and no custom rule defined for this ability, we assume
        // that user has permission for this action
        if (! $this->hasCustomAbilityRule($ability)) {
            return true;
        }

        // otherwise if user has this permission but custom rule is defined
        // we will go into this custom rule to verify it in details
        return null;
    }

    /**
     * Get permission name.
     *
     * @param string $ability
     *
     * @return string
     * @throws \Exception
     */
    protected function getPermissionName($ability)
    {
        $permissionMappings = $this->getPermissionMappings();

        // if we have full mapping for ability, we will use it
        if (isset($permissionMappings[$ability])) {
            return $permissionMappings[$ability];
        }

        // otherwise we will use default schema with group

        // we need to have group name defined in policy
        if ($this->group === null) {
            $this->log->error('You need to set group property in ' . __CLASS__);
            throw new \Exception('No group policy defined');
        }

        // we verify whether we have ability name mapping defined - if yes, we
        // will use it
        $abilityMappings = $this->getAbilityMappings();
        $ability = (isset($abilityMappings[$ability]))
            ? $abilityMappings[$ability] : $ability;

        return $this->group . '.' . $ability;
    }

    /**
     * Get super roles (roles that have all permissions and no further checks
     * won't be made for them).
     *
     * @return array
     */
    protected function getSuperRoles()
    {
        return (array) $this->config->get('authorize.super_roles', []);
    }

    /**
     * Verifies if there are any custom rules defined for given ability.
     *
     * @param string $ability
     *
     * @return bool
     */
    protected function hasCustomAbilityRule($ability)
    {
        return method_exists($this, $ability);
    }

    /**
     * Get request.
     *
     * @return Request
     */
    protected function getRequest()
    {
        return $this->request;
    }

    /**
     * Get ability mappings to replace some actions with others. For example for
     * create or edit it's very likely we need to have permission to store or
     * update so in case we don't have them there's usually no point to display
     * form or return data in those cases.
     *
     * @return array
     */
    protected function getAbilityMappings()
    {
        return [
            'create' => 'store',
            'edit' => 'update',
        ];
    }

    /**
     * In case we want to define custom mappings, we could do this. For example
     * if for many/all policies we would like to have in whole system only such
     * permissions like 'store', 'update' (no matter of resource), we could
     * create here the following map: ['store' => 'store', 'update' => 'update']
     * and in this case depending on assigned permission, users would have
     * permission (or not) to store/update everything in whole application. It
     * could be also useful in case for 2 actions we need same permission.
     *
     * @return array
     */
    protected function getPermissionMappings()
    {
        return [];
    }
}
