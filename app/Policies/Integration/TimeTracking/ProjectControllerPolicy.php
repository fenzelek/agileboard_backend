<?php

namespace App\Policies\Integration\TimeTracking;

use App\Policies\BasePolicy;

class ProjectControllerPolicy extends BasePolicy
{
    protected $group = 'time-tracking-project';
}
