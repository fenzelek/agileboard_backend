<?php

namespace App\Modules\Agile\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Db\Project;
use App\Models\Db\Sprint;
use App\Modules\Agile\Events\CreateStatusesEvent;
use App\Modules\Agile\Events\UpdateStatusesEvent;
use App\Modules\Agile\Http\Requests\StatusStore;
use App\Modules\Agile\Http\Requests\StatusUpdate;
use App\Modules\Agile\Http\Requests\StatusIndex;
use App\Modules\Agile\Services\TicketIndexService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class StatusController extends Controller
{
    /**
     *  Display list of statuses.
     *
     * @param StatusIndex $request
     * @param Project $project
     *
     * @param TicketIndexService $ticket_service
     *
     * @return JsonResponse
     */
    public function index(StatusIndex $request, Project $project, TicketIndexService $ticket_service)
    {
        $statuses = $project->statuses()->orderBy('priority');

        if ($request->input('tickets')) {
            $statuses->with(['tickets' => function ($query) use ($project, $request, $ticket_service) {
                $query = $ticket_service->getTicketsViaPermissions($project, $query);

                //filter by sprint
                if ($request->has('sprint_ids')) {
                    $query->whereIn('sprint_id', $request->input('sprint_ids'));
                } elseif ($request->input('backlog') == 1) {
                    $query->where('sprint_id', 0);
                } else {
                    //all active sprints
                    $sprints = $project->sprints()
                        ->where('status', Sprint::ACTIVE)
                        ->pluck('id')
                        ->toArray();
                    $query->whereIn('sprint_id', $sprints);
                }

                //filter by story
                if ($request->has('story_ids')) {
                    $query->whereHas('stories', function ($query) use ($request, $project) {
                        $query->whereIn('story_id', $request->input('story_ids'))
                            ->where('project_id', $project->id);
                    })->get();
                }

                $query->where('hidden', false)
                    ->orderBy('priority')
                    ->with([
                        'stories',
                        'assignedUser',
                        'parentTickets',
                        'subTickets',
//                        'sprint' => function ($q) {
//                            $q->select('name');
//                        },
                        ])
                    ->withCount('comments', 'files');
            }]);
        }
        $statuses = $statuses->get();

        return ApiResponse::responseOk($statuses, 200);
    }

    /**
     * Create new statuses.
     *
     * @param StatusStore $request
     * @param Project $project
     *
     * @return JsonResponse
     */
    public function store(StatusStore $request, Project $project)
    {
        DB::transaction(function () use ($request, $project) {
            $priority = 0;
            foreach ($request->input('statuses') as $status) {
                $project->statuses()->create([
                    'name' => $status['name'],
                    'priority' => ++$priority,
                ]);
            }
        });

        event(new CreateStatusesEvent($project));

        return ApiResponse::responseOk($project->statuses, 201);
    }

    /**
     * Update statuses.
     *
     * @param StatusUpdate $request
     * @param Project $project
     *
     * @return JsonResponse
     */
    public function update(StatusUpdate $request, Project $project)
    {
        DB::transaction(function () use ($request, $project) {
            $priority = 0;
            foreach ($request->input('statuses') as $status) {
                if ($status['id'] == 0) {
                    $project->statuses()->create([
                        'name' => $status['name'],
                        'priority' => ++$priority,
                    ]);
                } else {
                    $status_model = $project->statuses()->findOrFail($status['id']);

                    if ($status['delete']) {
                        $status_model->tickets()->update(['status_id' => $status['new_status']]);
                        $status_model->delete();
                    } else {
                        $status_model->update([
                            'name' => $status['name'],
                            'priority' => ++$priority,
                        ]);
                    }
                }
            }
        });

        event(new UpdateStatusesEvent($project));

        return ApiResponse::responseOk($project->statuses, 200);
    }
}
