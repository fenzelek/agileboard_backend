<?php

namespace App\Modules\Integration\Services\ManualActivityTools;

use App\Models\Db\Integration\TimeTracking\Activity;
use App\Modules\Integration\Services\Contracts\RemoveActivityProvider;

class ManualActivityValidator implements \App\Modules\Integration\Services\Contracts\ManualActivityValidator
{
    public function validate(RemoveActivityProvider $activity_data_provider):bool
    {
        if (! $this->userHasGivenActivities($activity_data_provider)) {
            return false;
        }

        if ($this->isActivitiesApproved($activity_data_provider)) {
            return false;
        }

        return true;
    }

    /**
     * @param $search_activities
     * @return mixed
     */
    protected function scopeForUser($search_activities)
    {
        //skip for owner/admin action
        return $search_activities;
    }

    private function userHasGivenActivities(RemoveActivityProvider $activity_data_provider): bool
    {
        $search_activities = $this->searchActivity($activity_data_provider);
        $scoped_search_activities = $this->scopeForUser($search_activities);
        $activities_ids = $this->filterByCompany($scoped_search_activities, $activity_data_provider)
            ->get()->pluck('id');

        return collect($activity_data_provider->getActivitiesIds())->diff($activities_ids)->isEmpty();
    }

    private function isActivitiesApproved(RemoveActivityProvider $activity_data_provider): bool
    {
        return false;
    }

    /**
     * @param RemoveActivityProvider $activity_data_provider
     * @return mixed
     */
    private function searchActivity(RemoveActivityProvider $activity_data_provider)
    {
        return Activity::whereIn('id', $activity_data_provider->getActivitiesIds());
    }

    /**
     * @param $scoped_search_activities
     * @param RemoveActivityProvider $activity_data_provider
     * @return mixed
     */
    private function filterByCompany($scoped_search_activities, RemoveActivityProvider $activity_data_provider)
    {
        return $scoped_search_activities->whereHas('integration', function ($q) use ($activity_data_provider) {
            $q->where('company_id', $activity_data_provider->getCompanyId());
        });
    }
}
