<?php

declare(strict_types=1);

namespace App\Modules\Integration\Services;

use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\User;
use App\Models\Db\UserAvailability;
use App\Models\Other\RoleType;
use App\Modules\Integration\Models\ActivityReportDto;
use App\Modules\Integration\Models\ManualTicketActivityDto;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class ActivityReport
{
    private User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @return Collection|ActivityReportDto[]
     */
    public function report(Carbon $day, int $selected_company_id): Collection
    {
        return $this->getUsersWithActivities($day, $selected_company_id)
            ->map(function (User $user) {
                $manual_activities = true;
                $manual_activities_seconds_sum = $this->sumActivities($user->activities, $manual_activities);
                $tracked_activities_seconds_sum = $this->sumActivities($user->activities, ! $manual_activities);

                $available = $this->getUserIsAvailable($user->availabilities);
                $availability_seconds = $available ? $this->getAvailabilitySeconds($user->availabilities) : null;
                $work_progress = $available ?
                    ($manual_activities_seconds_sum+$tracked_activities_seconds_sum)/$availability_seconds
                    : null;

                return new ActivityReportDto(
                    $user->id,
                    $user->email,
                    $user->first_name,
                    $user->last_name,
                    $manual_activities_seconds_sum,
                    $tracked_activities_seconds_sum,
                    $available,
                    $availability_seconds,
                    $work_progress,
                    $this->mapActivitiesToDto(
                        $this->filterActivities($user->activities, $manual_activities)
                    )
                );
            });
    }

    /**
     * @return Collection|User[]
     */
    private function getUsersWithActivities(Carbon $day, int $selected_company_id): Collection
    {
        return $this->user->newQuery()
            ->active()
            ->inSelectedCompany($selected_company_id, RoleType::DEVELOPER)
            ->withAvailabilities($day, $day, $selected_company_id)
            ->with([
                'activities' => function (HasMany $builder) use ($day, $selected_company_id) {
                    $builder->companyId($selected_company_id)
                        ->whereDate('utc_started_at', $day)
                        ->groupBy(['user_id', 'ticket_id', 'external_activity_id'])
                        ->select(['ticket_id', 'user_id', 'external_activity_id', DB::raw('SUM(tracked) as tracked_sum')]);
                },
                'activities.ticket' => function ($query) {
                    $query->select('id', 'title', 'name');
                },
            ])->get();
    }

    /**
     * @param Collection|UserAvailability[] $availabilities
     */
    private function getUserIsAvailable(Collection $availabilities): bool
    {
        return ! $availabilities->where('available', true)->isEmpty();
    }

    /**
     * @param Collection|UserAvailability[] $availabilities
     */
    private function getAvailabilitySeconds(Collection $availabilities): int
    {
        $availability_seconds = 0;
        foreach ($availabilities as $availability) {
            if (! $availability->available || ! $availability->time_start || ! $availability->time_stop) {
                continue;
            }
            $start = Carbon::parse($availability->time_start);
            $stop = Carbon::parse($availability->time_stop);

            $availability_seconds += $stop->diffInSeconds($start);
        }

        return $availability_seconds;
    }

    /**
     * @return Collection|ManualTicketActivityDto[]
     */
    private function mapActivitiesToDto(Collection $activities): Collection
    {
        return $activities->map(function (Activity $activity) {
            return new ManualTicketActivityDto(
                $activity->ticket_id,
                $activity->ticket->title,
                $activity->ticket->name,
                (int) $activity->tracked_sum
            );
        });
    }

    private function sumActivities(Collection $activities, bool $manual)
    {
        return $this->filterActivities($activities, $manual)->sum('tracked_sum');
    }

    private function filterActivities(Collection $activities, bool $manual): Collection
    {
        return $activities->filter(function (Activity $activity) use ($manual) {
            return $activity->manual === $manual;
        })->values();
    }
}
