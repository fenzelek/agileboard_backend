<?php

namespace App\Modules\CalendarAvailability\Contracts;

use App\Models\Db\User;
use Illuminate\Support\Collection;

interface NotificationOvertimeServiceInterface
{
    public function notify(Collection $availabilities, User $process_user): void;
}
