<?php

namespace App\Modules\TimeTracker\Services;

use App\Models\Db\Integration\TimeTracking\Activity;
use App\Modules\TimeTracker\Http\Requests\Contracts\GetScreenshotsQueryData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Screenshots
{
    private Activity $activity;

    public function __construct(Activity $activity)
    {
        $this->activity = $activity;
    }

    /**
     * @param GetScreenshotsQueryData $screenshots_request_data
     * @return Collection|Activity[]
     */
    public function get(GetScreenshotsQueryData $screenshots_request_data):Collection
    {
        return $this->activity
            ->newModelQuery()
            ->with(['screens', 'ticket', 'project'])
            ->whereUserId($screenshots_request_data->getUserId())
            ->forDate($screenshots_request_data->getDate())
            ->whenProjectId($screenshots_request_data->getProjectId())
            ->companyId($screenshots_request_data->getSelectedCompanyId())
            ->get();
    }
}
