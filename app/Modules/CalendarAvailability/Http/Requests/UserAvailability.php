<?php

namespace App\Modules\CalendarAvailability\Http\Requests;

use App\Modules\CalendarAvailability\Contracts\UserAvailabilityInterface;
use App\Modules\CalendarAvailability\Models\UserAvailabilitySourceType;
use Illuminate\Support\Arr;

class UserAvailability implements UserAvailabilityInterface
{
    private array $raw_user_availability;

    /**
     * @param array $raw_user_availability
     */
    public function __construct(array $raw_user_availability)
    {
        $this->raw_user_availability = $raw_user_availability;
    }

    public function getStartTime(): string
    {
        return Arr::get($this->raw_user_availability, 'time_start');
    }

    public function getStopTime(): string
    {
        return Arr::get($this->raw_user_availability, 'time_stop');
    }

    public function getOvertime(): bool
    {
        return Arr::get($this->raw_user_availability, 'overtime', false);
    }

    public function getAvailable(): bool
    {
        return Arr::get($this->raw_user_availability, 'available');
    }

    public function getDescription(): string
    {
        return Arr::get($this->raw_user_availability, 'description', '');
    }

    public function getSource(): string
    {
        return UserAvailabilitySourceType::INTERNAL;
    }
}
