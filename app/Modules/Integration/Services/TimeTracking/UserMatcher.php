<?php

namespace App\Modules\Integration\Services\TimeTracking;

use App\Models\Db\Integration\Integration;
use App\Models\Db\Integration\TimeTracking\User;
use App\Models\Db\User as SystemUser;

class UserMatcher
{
    /**
     * System user model.
     *
     * @var SystemUser
     */
    protected $system_user;

    /**
     * UserMatcher constructor.
     *
     * @param SystemUser $system_user
     */
    public function __construct(SystemUser $system_user)
    {
        $this->system_user = $system_user;
    }

    /**
     * Find and set matching user for given time tracking user.
     *
     * @param User $user
     *
     * @return User|null
     */
    public function process(User $user)
    {
        // if user is already matched nothing should be done
        if ($user->hasMatchingSystemUser()) {
            return null;
        }

        $system_user = $this->findMatch($user);

        // no match - we cannot do anything
        if (! $system_user) {
            return null;
        }

        $user->setSystemUser($system_user);

        return $user;
    }

    /**
     * Find matching user by email for given integration.
     *
     * @param string $email
     * @param Integration $integration
     *
     * @return int|null
     */
    public function findMatchingUserId($email, Integration $integration)
    {
        $match = $this->findMatchByEmail($email, $integration);

        return $match ? $match->id : null;
    }

    /**
     * Find match for given time tracking user.
     *
     * @param User $user
     *
     * @return SystemUser|null
     */
    protected function findMatch(User $user)
    {
        return $this->findMatchByEmail($user->external_user_email, $user->integration);
    }

    /**
     * Find user with given email for given integration.
     *
     * @param string $email
     * @param Integration $integration
     *
     * @return SystemUser|null
     */
    protected function findMatchByEmail($email, Integration $integration)
    {
        return $integration->company->users()->where('email', $email)->first();
    }
}
