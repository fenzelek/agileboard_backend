<?php

namespace App\Modules\CalendarAvailability\Services\AvailabilityTools;

use App\Models\Db\User;
use App\Modules\CalendarAvailability\Contracts\NotificationOvertimeServiceInterface;
use Illuminate\Support\Collection;

class NotificationOvertimeService implements NotificationOvertimeServiceInterface
{
    private function isConfirmed(array $availabilities): bool
    {
        return false;
    }

    public function notify(Collection $availabilities, User $user): void
    {
    }
}
