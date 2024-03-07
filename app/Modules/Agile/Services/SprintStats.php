<?php

namespace App\Modules\Agile\Services;

use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\Project;
use Illuminate\Support\Collection;

class SprintStats
{
    private Project $project;
    private Activity $activity;
    private ?array $stories;

    public function __construct(Activity $activity)
    {
        $this->activity = $activity;
    }

    public function reportFor(Collection $sprints, Project $project, ?array $stories):Collection
    {
        $this->project = $project;
        $this->stories = $stories;
        foreach ($sprints as $sprint) {
            $un_started_estimations = +$this->getUnStartedEstimations($sprint);

            $expected_final = $this->getTrackedTimeWithoutInProgress($sprint);
            $expected_final += $this->getExpectedTimeFromInProgress($sprint);
            $expected_final += $un_started_estimations;

            $sprint->stats = [
                'un_started_estimations' => $un_started_estimations,
                'expected_final' => $expected_final,
            ];
        }

        return $sprints;
    }

    /**
     * get un-started estimations.
     *
     * @param $sprint
     * @return mixed
     */
    private function getUnStartedEstimations($sprint)
    {
        return $sprint->tickets()
            ->selectRaw('SUM(estimate_time) as estimate_time_summary')
            ->leftJoin('time_tracking_activities', 'time_tracking_activities.ticket_id', 'tickets.id')
            ->whereNull('tracked')
            ->where(function ($q) {
                if ($this->project->status_for_calendar_id) {
                    $q->where('status_id', '!=', $this->project->status_for_calendar_id);
                }
                $this->filterTickets($q);
            })->get()->first()->estimate_time_summary;
    }

    /**
     * get tracked time without tickets in progress.
     *
     * @param Sprint $sprint
     * @return mixed
     */
    private function getTrackedTimeWithoutInProgress($sprint)
    {
        return $sprint->tickets()
            ->selectRaw('SUM(tracked) as tracked_summary')
            ->where(function ($q) {
                if ($this->project->status_for_calendar_id) {
                    $q->where('status_id', '!=', $this->project->status_for_calendar_id);
                }
                $this->filterTickets($q);
            })->join('time_tracking_activities', 'time_tracking_activities.ticket_id', 'tickets.id')
            ->get()->first()->tracked_summary;
    }

    /**
     * get expected time from status in progress.
     *
     * @param $sprint
     * @return int
     */
    private function getExpectedTimeFromInProgress($sprint)
    {
        if (! $this->project->status_for_calendar_id) {
            return 0;
        }

        /**
         * @var \App\Models\Db\Sprint $sprint
         */
        $tickets = $sprint->tickets()
            ->selectRaw('SUM(tracked) as tracked, estimate_time')
            ->where('status_id', $this->project->status_for_calendar_id)
            ->where(function ($q) {
                $this->filterTickets($q);
            })
            ->leftJoin('time_tracking_activities', 'time_tracking_activities.ticket_id', 'tickets.id')
            ->groupBy('tickets.id')
            ->get();
        $sum = 0;

        foreach ($tickets as $ticket) {
            if ($ticket->estimate_time > $ticket->tracked) {
                $sum += $ticket->estimate_time;
            } else {
                $sum += $ticket->tracked;
            }
        }

        return $sum;
    }

    /**
     * Filter tickets.
     *
     * @param $query
     */
    private function filterTickets($query)
    {
        $query->where('tickets.project_id', $this->project->id);
        if ($this->stories) {
            $query->whereHas('stories', function ($q) {
                $q->whereIn('stories.id', $this->stories);
            });
        }
    }
}
