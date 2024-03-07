<?php

namespace App\Modules\CalendarAvailability\Services\AvailabilityFactory;

use App\Modules\CalendarAvailability\Contracts\AdderAvailabilityInterface;
use App\Modules\CalendarAvailability\Contracts\DestroyerAvailabilityInterface;
use App\Modules\CalendarAvailability\Contracts\NotificationOvertimeServiceInterface;
use App\Modules\CalendarAvailability\Contracts\ProcessAvailabilityInterface;
use App\Modules\CalendarAvailability\Services\AvailabilityManager;
use App\Modules\CalendarAvailability\Services\AvailabilityTools\Validators\ServeAvailabilityValidator;
use Illuminate\Container\Container;

abstract class CommonStoreAvailabilityManagerFactory
{
    protected Container $app;

    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    protected function makeStoreAvailabilityManager(
        ServeAvailabilityValidator $validator,
        ProcessAvailabilityInterface $process_availability,
        AdderAvailabilityInterface $adder_availability,
        DestroyerAvailabilityInterface $destroyer_availability,
        NotificationOvertimeServiceInterface $notification_service
    ): AvailabilityManager {
        return $this->app->make(AvailabilityManager::class, [
            'validator' => $validator,
            'process_availability' => $process_availability,
            'destroyer_availability' => $destroyer_availability,
            'adder_availability' => $adder_availability,
            'notify_service' => $notification_service,
        ]);
    }
}
