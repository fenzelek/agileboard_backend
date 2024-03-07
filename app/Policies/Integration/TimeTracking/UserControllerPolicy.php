<?php

namespace App\Policies\Integration\TimeTracking;

use App\Policies\BasePolicy;

class UserControllerPolicy extends BasePolicy
{
    protected $group = 'time-tracking-user';
}
