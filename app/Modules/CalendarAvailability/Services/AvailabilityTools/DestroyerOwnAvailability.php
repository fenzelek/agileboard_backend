<?php

namespace App\Modules\CalendarAvailability\Services\AvailabilityTools;

use App\Models\Db\User;
use App\Models\Db\UserAvailability;
use App\Models\Other\UserAvailabilityStatusType;
use App\Modules\CalendarAvailability\Contracts\DestroyerAvailabilityInterface;

class DestroyerOwnAvailability extends DestroyerAvailability implements DestroyerAvailabilityInterface
{
    /**
     * @param User $user
     * @param UserAvailability $user_availability
     */
    public function __construct(User $user, UserAvailability $user_availability)
    {
        parent::__construct($user, $user_availability);
    }

    public function destroy(int $company_id, string $day)
    {
        return parent::destroy($company_id, $day);
    }

    protected function scopePerStatus($destroy_scope)
    {
        return $destroy_scope->whereNotIn('status', [UserAvailabilityStatusType::CONFIRMED]);
    }
}
