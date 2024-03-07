<?php

namespace App\Modules\CalendarAvailability\Services;

use App\Models\Db\User;
use App\Modules\CalendarAvailability\Contracts\AddDaysOffInterface;
use App\Modules\CalendarAvailability\Contracts\AdderAvailabilityInterface;
use App\Modules\CalendarAvailability\Contracts\DestroyerAvailabilityInterface;
use App\Modules\CalendarAvailability\Exceptions\InvalidTimePeriodAvailability;
use App\Modules\CalendarAvailability\Models\ProcessListAvailabilityDTO;
use App\Modules\CalendarAvailability\Models\UserAvailabilityAdapter;
use App\Modules\CalendarAvailability\Services\AvailabilityTools\Validators\DaysOffValidator;
use Illuminate\Support\Facades\DB;

class DaysOffManager
{
    private DaysOffValidator $validator;
    private DestroyerAvailabilityInterface $destroyer_availability;
    private AdderAvailabilityInterface $adder_availability;

    /**
     * @param DaysOffValidator $validator
     * @param DestroyerAvailabilityInterface $destroyer_availability
     * @param AdderAvailabilityInterface $adder_availability
     */
    public function __construct(
        DaysOffValidator $validator,
        DestroyerAvailabilityInterface $destroyer_availability,
        AdderAvailabilityInterface $adder_availability
    ) {
        $this->validator = $validator;
        $this->destroyer_availability = $destroyer_availability;
        $this->adder_availability = $adder_availability;
    }

    /**
     * @throws InvalidTimePeriodAvailability
     */
    public function storeAvailability(AddDaysOffInterface $availability_provider):bool
    {
        $company_id = $availability_provider->getSelectedCompanyId();

        $days_off = $availability_provider->getDays();

        if (! $this->validator->validate($days_off)) {
            throw new InvalidTimePeriodAvailability();
        }

        foreach ($days_off as $day_off){

            $availability = new ProcessListAvailabilityDTO([new UserAvailabilityAdapter($day_off)]);
            DB::transaction(function () use ($availability, $company_id, $day_off) {
                $this->destroyer_availability->destroy($company_id, $day_off->getDate()->format('Y-m-d'));
                $this->adder_availability->add($availability, $company_id, $day_off->getDate()->format('Y-m-d'));
            });
        }

        return true;
    }

    public function update(){

    }


}
