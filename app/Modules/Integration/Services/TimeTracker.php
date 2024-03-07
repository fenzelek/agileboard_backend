<?php
declare(strict_types=1);

namespace App\Modules\Integration\Services;

use App\Models\Db\Integration\TimeTracking\Activity;
use App\Modules\Integration\Models\DailyActivity;
use App\Modules\Integration\Services\Contracts\DailyActivityEntryData;
use Illuminate\Support\Collection;

class TimeTracker
{
    private Activity $activity;

    public function __construct(Activity $activity)
    {
        $this->activity = $activity;
    }

    /**
     * @param DailyActivityEntryData $daily_activity_entry_data
     *
     * @return Collection|DailyActivity[]
     */
    public function getTimeSummary(DailyActivityEntryData $daily_activity_entry_data): Collection
    {
        $time_zone_offset = $daily_activity_entry_data->getTimeZoneOffset();

        $started = $daily_activity_entry_data
            ->getStartedAt()
            ->addHours($time_zone_offset)
            ->toDateTimeString();

        $finished = $daily_activity_entry_data
            ->getFinishedAt()
            ->addHours($time_zone_offset)
            ->toDateTimeString();

        return $this->activity->newModelQuery()
            ->onlyNoTrashed()
            ->selectRaw('DATE(DATE_ADD(utc_started_at, INTERVAL ' . $time_zone_offset .
                ' HOUR)) as date, SUM(tracked) as tracked')
            ->companyId($daily_activity_entry_data->getCompanyId())
            ->where('user_id', $daily_activity_entry_data->getUserId())
            ->where(function ($q) use ($started, $finished) {
                $q->whereBetween('utc_started_at', [$started, $finished])
                    ->whereBetween('utc_finished_at', [$started, $finished]);
            })
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();
    }
}
