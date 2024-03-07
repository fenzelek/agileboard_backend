<?php

namespace App\Modules\CalendarAvailability\Services\AvailabilityTools;

use App\Models\Db\UserAvailability;

class GetUserAvailability
{
    private UserAvailability $user_availability;

    /**
     * @param UserAvailability $user_availability
     */
    public function __construct(UserAvailability $user_availability)
    {
        $this->user_availability = $user_availability;
    }

    public function get($users, int $company_id, $days)
    {
        return $this->user_availability->newModelQuery()
            ->whereIn('user_id', (array) $users)
            ->whereIn('day', (array) $days)
            ->where('company_id', '=', $company_id)
            ->orderBy('user_id', 'ASC')
            ->orderBy('day', 'ASC')
            ->orderBy('time_start', 'ASC');
    }
}
