<?php

namespace App\Modules\CalendarAvailability\Services;

use App\Models\Db\User;
use App\Modules\CalendarAvailability\Contracts\AdderAvailabilityInterface;
use App\Modules\CalendarAvailability\Contracts\AvailabilityStore;
use App\Modules\CalendarAvailability\Contracts\DestroyerAvailabilityInterface;
use App\Modules\CalendarAvailability\Contracts\NotificationOvertimeServiceInterface;
use App\Modules\CalendarAvailability\Contracts\ProcessAvailabilityInterface;
use App\Modules\CalendarAvailability\Exceptions\InvalidTimePeriodAvailability;
use App\Modules\CalendarAvailability\Services\AvailabilityTools\GetUserAvailability;
use App\Modules\CalendarAvailability\Services\AvailabilityTools\Validators\ServeAvailabilityValidator;
use Illuminate\Support\Facades\DB;

class AvailabilityManager
{
    private ServeAvailabilityValidator $validator;
    private ProcessAvailabilityInterface $process_availability;
    private DestroyerAvailabilityInterface $destroyer_availability;
    private AdderAvailabilityInterface $adder_availability;
    private GetUserAvailability $get_availability;
    private NotificationOvertimeServiceInterface $notify_service;

    /**
     * @param ServeAvailabilityValidator $validator
     * @param ProcessAvailabilityInterface $process_availability
     * @param DestroyerAvailabilityInterface $destroyer_availability
     * @param AdderAvailabilityInterface $adder_availability
     * @param GetUserAvailability $get_availability
     * @param NotificationOvertimeServiceInterface $notify_service
     */
    public function __construct(
        ServeAvailabilityValidator $validator,
        ProcessAvailabilityInterface $process_availability,
        DestroyerAvailabilityInterface $destroyer_availability,
        AdderAvailabilityInterface $adder_availability,
        GetUserAvailability $get_availability,
        NotificationOvertimeServiceInterface $notify_service
    ) {
        $this->validator = $validator;
        $this->process_availability = $process_availability;
        $this->destroyer_availability = $destroyer_availability;
        $this->adder_availability = $adder_availability;
        $this->get_availability = $get_availability;
        $this->notify_service = $notify_service;
    }

    /**
     * @throws InvalidTimePeriodAvailability
     */
    public function storeAvailability(AvailabilityStore $availability_provider, User $process_user)
    {
        $company_id = $availability_provider->getCompanyId();
        $day = $availability_provider->getDay();

        if (! $this->validator->validate($availability_provider)) {
            throw new InvalidTimePeriodAvailability();
        }
        $availability =
            $this->process_availability->processAvailability($availability_provider);
        DB::transaction(function () use ($availability, $process_user, $company_id, $day) {
            $this->destroyer_availability->destroy($company_id, $day);
            $this->adder_availability->add($availability, $company_id, $day);
        });

        $availabilities = $this->get_availability->get($process_user->id, $company_id, $day)->get();

        $this->notify_service->notify($availabilities, $process_user);

        return $availabilities;
    }
}
