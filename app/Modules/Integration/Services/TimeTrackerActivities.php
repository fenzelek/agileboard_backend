<?php

declare(strict_types=1);

namespace App\Modules\Integration\Services;

use App\Models\Db\Integration\TimeTracking\Activity;
use App\Modules\Integration\Models\ActivityExportDto;
use App\Modules\Integration\Models\ActivitySummaryExportDto;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class TimeTrackerActivities
{
    public function getActivitiesForExport(EloquentCollection $activities): Collection
    {
        return $activities
            ->load([
                'user',
                'project',
                'ticket',
                'ticket.sprint',
                'timeTrackingUser',
                'timeTrackingNote',
            ])->map(function (Activity $activity) {
                $user = $activity->user;
                $project = $activity->project;
                $ticket = $activity->ticket;
                $sprint = $activity->ticket ? $activity->ticket->sprint : null;

                return new ActivityExportDto(
                    $activity->id,
                    $user ? $user->first_name : '',
                    $user ? $user->last_name : '',
                    $activity->utc_started_at,
                    $activity->utc_finished_at,
                    $activity->tracked,
                    $project ? $project->name : '',
                    $sprint ? $sprint->name : '',
                    $ticket ? $ticket->title : null,
                    $activity->comment
                );
            });
    }

    public function getSummaryActivitiesForExport(EloquentCollection $activities): Collection
    {
        return $activities->load([
                'user',
                'project',
                'ticket',
                'ticket.sprint',
                'timeTrackingUser',
                'timeTrackingNote',
            ])
            ->groupBy(function (Activity $activity) {
                return $activity->ticket_id . '-' . $activity->user_id;
            })
            ->map(function (Collection $activities, $group) {
                /** @var Activity $activity */
                $activity = $activities->first();
                $user = $activity->user;
                $project = $activity->project;
                $ticket = $activity->ticket;
                $sprint = $activity->ticket ? $activity->ticket->sprint : null;

                return new ActivitySummaryExportDto(
                    $activity->ticket_id,
                    $ticket ? $ticket->title??'' : '',
                    $ticket ? $ticket->name??'' : '',
                    $ticket ? $ticket->description??'' : '',
                    $ticket ? $ticket->estimate_time : 0,
                    $activities->sum(fn (Activity $activity) => $activity->tracked),
                    $user ? $user->first_name : '',
                    $user ? $user->last_name : '',
                    $sprint ? $sprint->name : '',
                    $project ? $project->name : ''
                );
            });
    }
}
