<?php

namespace App\Modules\CalendarAvailability\Contracts;

interface UserAvailabilityInterface
{
    public function getStartTime(): string;
    public function getStopTime(): string;
    public function getOvertime(): bool;
    public function getAvailable(): bool;
    public function getDescription(): string;
    public function getSource(): string;
}
