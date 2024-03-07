<?php

namespace App\Modules\CalendarAvailability\Services\AvailabilityFactory;

use App\Models\Db\User;
use App\Models\Other\UserAvailabilityStatusType;
use App\Modules\CalendarAvailability\Services\AvailabilityManager;
use App\Modules\CalendarAvailability\Services\AvailabilityTools\AdderAvailability;
use App\Modules\CalendarAvailability\Services\AvailabilityTools\DestroyerAvailability;
use App\Modules\CalendarAvailability\Services\AvailabilityTools\NotificationOvertimeService;
use App\Modules\CalendarAvailability\Services\AvailabilityTools\ProcessUserAvailability;
use App\Modules\CalendarAvailability\Services\AvailabilityTools\Validators\ServeAvailabilityValidator;

class StoreAvailabilityManagerFactory extends CommonStoreAvailabilityManagerFactory
{
    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function create(User $user): AvailabilityManager
    {
        $validator = $this->app->make(ServeAvailabilityValidator::class, ['user' => $user]);
        $process_availability = $this->makeAvailabilityProcessor($user);
        $destroyer_availability = $this->makeAvailabilityDestroyer($user);
        $adder_availability = $this->app->make(
            AdderAvailability::class,
            ['user' => $user, 'status' => UserAvailabilityStatusType::CONFIRMED]
        );
        $notify_service = $this->app->make(NotificationOvertimeService::class);

        return $this->makeStoreAvailabilityManager(
            $validator,
            $process_availability,
            $adder_availability,
            $destroyer_availability,
            $notify_service
        );
    }

    private function makeAvailabilityProcessor(User $user): ProcessUserAvailability
    {
        return $this->app->make(ProcessUserAvailability::class, ['user' => $user]);
    }

    private function makeAvailabilityDestroyer(User $user): DestroyerAvailability
    {
        return $this->app->make(DestroyerAvailability::class, ['user' => $user]);
    }
}
