<?php

namespace App\Modules\CalendarAvailability\Services\AvailabilityFactory;

use App\Models\Db\User;
use App\Models\Other\UserAvailabilityStatusType;
use App\Modules\CalendarAvailability\Services\AvailabilityManager;
use App\Modules\CalendarAvailability\Services\AvailabilityTools\AdderAvailability;
use App\Modules\CalendarAvailability\Services\AvailabilityTools\DestroyerAvailability;
use App\Modules\CalendarAvailability\Services\AvailabilityTools\DestroyerOwnAvailability;
use App\Modules\CalendarAvailability\Services\AvailabilityTools\NotificationOvertimeOwnService;
use App\Modules\CalendarAvailability\Services\AvailabilityTools\ProcessUserAvailability;
use App\Modules\CalendarAvailability\Services\AvailabilityTools\ProcessUserOwnAvailability;
use App\Modules\CalendarAvailability\Services\AvailabilityTools\Validators\ServeAvailabilityValidator;
use App\Modules\CalendarAvailability\Services\AvailabilityTools\Validators\DaysOffValidator;
use App\Modules\CalendarAvailability\Services\DaysOffManager;
use Illuminate\Contracts\Container\Container;

class DaysOffManagerFactory
{
    private Container $app;

    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    public function create(User $user): DaysOffManager
    {
        $validator = $this->app->make(DaysOffValidator::class, ['user' => $user]);
        $destroyer_availability = $this->makeAvailabilityDestroyer($user);
        $adder_availability = $this->app->make(AdderAvailability::class, ['user' => $user, 'status' => UserAvailabilityStatusType::CONFIRMED]);

        return $this->app->make(DaysOffManager::class, [
            'validator' => $validator,
            'destroyer_availability' => $destroyer_availability,
            'adder_availability' => $adder_availability,
        ]);
    }

    private function makeAvailabilityDestroyer(User $user): DestroyerAvailability
    {
        return $this->app->make(DestroyerAvailability::class, ['user' => $user]);
    }
}
