<?php

namespace App\Modules\CalendarAvailability\Services\AvailabilityTools;

use App\Models\Db\User;
use App\Models\Db\UserAvailability;
use App\Modules\CalendarAvailability\Contracts\AdderAvailabilityInterface;
use App\Modules\CalendarAvailability\Contracts\UserAvailabilityInterface;
use App\Modules\CalendarAvailability\Models\ProcessListAvailabilityDTO;

class AdderAvailability implements AdderAvailabilityInterface
{
    private User $user;
    private UserAvailability $user_availability;
    private string $status;

    /**
     * @param User $user
     * @param UserAvailability $user_availability
     * @param string $status
     */
    public function __construct(User $user, UserAvailability $user_availability, string $status)
    {
        $this->user = $user;
        $this->user_availability = $user_availability;
        $this->status = $status;
    }

    public function add(ProcessListAvailabilityDTO $availabilities, int $company_id, string $day)
    {
        $availabilities->getAvailability()->each(function (UserAvailabilityInterface $availability) use ($company_id, $day) {
            $this->user_availability->create([
                'time_start' => $availability->getStartTime(),
                'time_stop' => $availability->getStopTime(),
                'description' => $availability->getDescription(),
                'overtime' => $availability->getOvertime(),
                'available' => $availability->getAvailable(),
                'user_id' => $this->user->id,
                'company_id' => $company_id,
                'day' => $day,
                'status' => $this->status,
                'source' => $availability->getSource(),
            ]);
        });
    }
}
