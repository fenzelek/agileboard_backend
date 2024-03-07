<?php

namespace App\Policies\Integration\TimeTracking;

use App\Policies\BasePolicy;

class ActivityControllerPolicy extends BasePolicy
{
    protected $group = 'time-tracking-activity';
}
