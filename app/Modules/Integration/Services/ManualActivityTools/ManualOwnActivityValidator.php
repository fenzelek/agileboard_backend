<?php

namespace App\Modules\Integration\Services\ManualActivityTools;

use App\Models\Db\User;
use App\Modules\Integration\Services\Contracts\RemoveActivityProvider;

class ManualOwnActivityValidator extends ManualActivityValidator implements \App\Modules\Integration\Services\Contracts\ManualActivityValidator
{
    private User $user;

    public function validate(RemoveActivityProvider $activity_data_provider):bool
    {
        if (empty($this->user)) {
            return false;
        }

        return parent::validate($activity_data_provider);
    }

    public function forUser(User $user):void
    {
        $this->user = $user;
    }

    /**
     * @param $search_activities
     * @return mixed
     */
    protected function scopeForUser($search_activities)
    {
        return $search_activities->where('user_id', '=', $this->user->id);
    }
}
