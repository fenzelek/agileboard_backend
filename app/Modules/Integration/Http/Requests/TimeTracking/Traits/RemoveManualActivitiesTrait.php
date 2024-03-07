<?php

namespace App\Modules\Integration\Http\Requests\TimeTracking\Traits;

trait RemoveManualActivitiesTrait
{
    public function getActivitiesIds(): array
    {
        return $this->input('activities');
    }

    public function getCompanyId(): int
    {
        return (int) $this->query('selected_company_id');
    }
}
