<?php

namespace App\Modules\CalendarAvailability\Services\AvailabilityFactory;

use App\Models\Db\User;
use App\Models\Other\UserAvailabilityStatusType;
use App\Modules\CalendarAvailability\Services\AvailabilityManager;
use App\Modules\CalendarAvailability\Services\AvailabilityTools\AdderAvailability;
use App\Modules\CalendarAvailability\Services\AvailabilityTools\DestroyerOwnAvailability;
use App\Modules\CalendarAvailability\Services\AvailabilityTools\NotificationOvertimeOwnService;
use App\Modules\CalendarAvailability\Services\AvailabilityTools\ProcessUserOwnAvailability;
use App\Modules\CalendarAvailability\Services\AvailabilityTools\Validators\ServeAvailabilityValidator;

class StoreOwnAvailabilityManagerFactory extends CommonStoreAvailabilityManagerFactory
{
    public function create(User $user): AvailabilityManager
    {
        $validator = $this->app->make(ServeAvailabilityValidator::class, ['user' => $user]);
        $process_availability = $this->makeAvailabilityProcessor($user);
        $destroyer_availability = $this->makeAvailabilityDestroyer($user);
        $adder_availability =
            $this->app->make(AdderAvailability::class, ['user' => $user, 'status' => UserAvailabilityStatusType::ADDED]);
        $notify_service = $this->app->make(NotificationOvertimeOwnService::class);

        return $this->makeStoreAvailabilityManager(
            $validator,
            $process_availability,
            $adder_availability,
            $destroyer_availability,
            $notify_service
        );
    }

    private function makeAvailabilityProcessor(User $user): ProcessUserOwnAvailability
    {
        return $this->app->make(ProcessUserOwnAvailability::class, ['user' => $user]);
    }

    private function makeAvailabilityDestroyer(User $user): DestroyerOwnAvailability
    {
        return $this->app->make(DestroyerOwnAvailability::class, ['user' => $user]);
    }
}
