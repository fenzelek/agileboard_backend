<?php

namespace App\Modules\Agile\Services;

use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\Project;
use App\Models\Db\Sprint;
use App\Modules\Agile\Http\Requests\SprintIndex;

class SprintIndexService
{
    private $project;
    private $activity;
    private $request;

    /*
     * @var SprintStats
     */
    private $sprint_stats;

    public function __construct(SprintStats $sprint_stats)
    {
        $this->sprint_stats = $sprint_stats;
    }

    /**
     * Get sprints.
     *
     * @param SprintIndex $request
     * @param Project $project
     * @param Activity $activity
     * @param TicketIndexService $ticketIndexService
     * @return $this|\Illuminate\Database\Eloquent\Collection|mixed
     */
    public function getData(
        SprintIndex $request,
        Project $project,
        Activity $activity,
        TicketIndexService $ticketIndexService
    ) {
        $this->project = $project;
        $this->activity = $activity;
        $this->request = $request;

        $sprints = $project->sprints()->orderBy('priority');

        if ($request->input('status') == 'not-closed') {
            $sprints = $sprints->where('status', '!=', Sprint::CLOSED);
        } else {
            $sprints = $sprints->where('status', $request->input('status'));
        }

        if ($request->input('with_tickets')) {
            $sprints->with($this->tickets($ticketIndexService));
        }

        if (in_array($request->input('stats'), ['min', 'all'])) {
            $sprints = $sprints->with($this->minStats());
        }

        $sprints = $sprints->get();

        if ($request->input('with_tickets')) {
            foreach ($sprints as $k => $sprint) {
                $sprints[$k]->tickets = $ticketIndexService->checkActivityPermission($sprint->tickets);
            }
        }

        if ($request->input('with_backlog')) {
            $backlog = $this->makeBacklog();

            if ($request->input('with_tickets')) {
                $backlog->load($this->tickets($ticketIndexService));
                $backlog->tickets = $ticketIndexService->checkActivityPermission($backlog->tickets);
            }

            if (in_array($request->input('stats'), ['min', 'all'])) {
                $backlog = $backlog->load($this->minStats());
            }


            $sprints->push($backlog);
        }

        if ($request->input('stats') == 'all') {
            return $this->addData($sprints);
        }

        return $sprints;
    }

    /**
     * Make backlog.
     *
     * @return mixed
     */
    private function makeBacklog()
    {
        return factory(Sprint::class)->make([
            'id' => 0,
            'project_id' => $this->project->id,
            'name' => 'Backlog',
        ]);
    }

    /**
     * Get min stats.
     *
     * @return array
     */
    private function minStats()
    {
        return [
            'ticketsGeneralSummary' => function ($q) {
                $this->filterTickets($q);
            },
            'timeTrackingGeneralSummary' => function ($q) {
                $q->whereHas('ticket', function ($q) {
                    $this->filterTickets($q);
                });
            },
            'project',
        ];
    }

    /**
     * Get min tickets.
     *
     * @param TicketIndexService $ticketIndexService
     * @return array
     */
    private function tickets(TicketIndexService $ticketIndexService)
    {
        return [
            'tickets' => function ($q) use ($ticketIndexService) {
                $q->where('project_id', $this->project->id);
                $ticketIndexService->settingsTickets($this->request, $q, $this->project);
            },
        ];
    }

    /**
     * Filter tickets.
     *
     * @param $query
     */
    private function filterTickets($query)
    {
        $query->where('project_id', $this->project->id);
        if ($this->request->input('story_ids')) {
            $query->whereHas('stories', function ($q) {
                $q->whereIn('stories.id', $this->request->input('story_ids'));
            });
        }
    }

    /**
     * Add data time tracking for sprints.
     *
     * @param $sprints
     * @return mixed
     */
    private function addData($sprints)
    {
        $stories = $this->request->input('story_ids');

        return $this->sprint_stats->reportFor($sprints, $this->project, $stories);
    }
}
