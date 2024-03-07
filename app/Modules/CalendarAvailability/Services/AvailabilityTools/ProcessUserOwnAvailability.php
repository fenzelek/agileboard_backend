<?php

namespace App\Modules\CalendarAvailability\Services\AvailabilityTools;

use App\Models\Db\User;
use App\Models\Db\UserAvailability;
use App\Modules\CalendarAvailability\Contracts\AvailabilityStore;
use App\Modules\CalendarAvailability\Contracts\ProcessAvailabilityInterface;
use App\Modules\CalendarAvailability\Models\ProcessListAvailabilityDTO;

class ProcessUserOwnAvailability implements ProcessAvailabilityInterface
{
    private User $user;

    /**
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function processAvailability(AvailabilityStore $availability_provider): ProcessListAvailabilityDTO
    {
        $permitted_availability = $this->getPermittedAvailability($availability_provider);

        return new ProcessListAvailabilityDTO($permitted_availability);
    }

    protected function getPermittedAvailability(AvailabilityStore $availability_provider): array
    {
        $search_abilities = [];
        foreach ($availability_provider->getAvailabilities() as $availability) {
            $blocked_availability =
                UserAvailability::byCompanyId($availability_provider->getCompanyId())
                ->byUserId($this->user->id)
                ->forDay($availability_provider->getDay())
                ->confirmed()
                ->overlap($availability->getStartTime(), $availability->getStopTime())
                ->count();

            if (! $blocked_availability) {
                $search_abilities[] = $availability;
            }
        }

        return $search_abilities;
    }
}
