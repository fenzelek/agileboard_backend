<?php

namespace App\Policies;

use App\Models\Db\User;

class CalendarDayOffControllerPolicy extends BasePolicy
{
    protected $group = 'day-off';
}
