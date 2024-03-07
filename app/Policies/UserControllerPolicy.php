<?php

namespace App\Policies;

use App\Models\Db\User;

class UserControllerPolicy extends BasePolicy
{
    protected $group = 'user';

    public function update(User $user, $user_id)
    {
        // user can only update his own account
        return ((int) $user_id == $user->id);
    }
}
