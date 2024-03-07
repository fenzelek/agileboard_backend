<?php

namespace App\Modules\CalendarAvailability\Services\AvailabilityTools;

use App\Models\Db\User;
use App\Models\Db\UserAvailability;
use App\Modules\CalendarAvailability\Contracts\DestroyerAvailabilityInterface;

class DestroyerAvailability implements DestroyerAvailabilityInterface
{
    private User $user;
    private UserAvailability $user_availability;

    /**
     * @param User $user
     * @param UserAvailability $user_availability
     */
    public function __construct(User $user, UserAvailability $user_availability)
    {
        $this->user = $user;
        $this->user_availability = $user_availability;
    }

    public function destroy(int $company_id, string $day)
    {
        $destroy_scope = $this->destroyAvailabilities($company_id, $day);

        $destroy_scope = $this->scopePerStatus($destroy_scope);
        $destroy_scope->delete();
    }

    protected function destroyAvailabilities(int $company_id, string $day)
    {
        return $this->user_availability->newModelQuery()
            ->where('user_id', '=', $this->user->id)
            ->where('day', '=', $day)
            ->where('company_id', '=', $company_id);
    }

    protected function scopePerStatus($destroy_scope)
    {
        //  Owner/Admin allows remove User Availability no matter of status
        return $destroy_scope;
    }
}
