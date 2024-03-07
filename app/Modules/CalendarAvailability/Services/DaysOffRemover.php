<?php

namespace App\Modules\CalendarAvailability\Services;

use App\Models\Db\User;
use App\Models\Db\UserAvailability;
use App\Modules\CalendarAvailability\Contracts\AddDaysOffInterface;
use App\Modules\CalendarAvailability\Contracts\AdderAvailabilityInterface;
use App\Modules\CalendarAvailability\Contracts\DeleteDaysOffInterface;
use App\Modules\CalendarAvailability\Contracts\DestroyerAvailabilityInterface;
use App\Modules\CalendarAvailability\Exceptions\InvalidTimePeriodAvailability;
use App\Modules\CalendarAvailability\Models\ProcessListAvailabilityDTO;
use App\Modules\CalendarAvailability\Models\UserAvailabilityAdapter;
use App\Modules\CalendarAvailability\Models\UserAvailabilitySourceType;
use App\Modules\CalendarAvailability\Services\AvailabilityTools\Validators\DaysOffValidator;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class DaysOffRemover
{
    private UserAvailability $user_availability;

    public function __construct(UserAvailability $user_availability)
    {
        $this->user_availability = $user_availability;
    }

    public function delete(DeleteDaysOffInterface $days_off):int{

        return $this->user_availability->newQuery()
            ->byUserId($days_off->getUserId())
            ->byCompanyId($days_off->getSelectedCompanyId())
            ->whereSource(UserAvailabilitySourceType::EXTERNAL)
            ->whereDayIn($this->mapToDays($days_off))
            ->delete();
    }

    /**
     * @param  DeleteDaysOffInterface  $days_off
     * @return \Carbon\CarbonInterface[]
     */
    private function mapToDays(DeleteDaysOffInterface $days_off): array
    {
        return array_map(fn(CarbonInterface $day) => $day->format('Y-m-d'),$days_off->getDays());
    }
}
