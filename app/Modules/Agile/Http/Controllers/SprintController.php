<?php

namespace App\Modules\Agile\Http\Controllers;

use App\Http\Resources\SprintWithDetails;
use App\Http\Resources\TicketWithDetails;
use App\Models\Db\Integration\TimeTracking\Activity;
use App\Models\Db\Ticket;
use App\Modules\Agile\Events\ActiveSprintEvent;
use App\Modules\Agile\Events\ChangePrioritySprintEvent;
use App\Modules\Agile\Events\CloseSprintEvent;
use App\Modules\Agile\Events\CreateSprintEvent;
use App\Modules\Agile\Events\DeleteSprintEvent;
use App\Modules\Agile\Events\LockSprintEvent;
use App\Modules\Agile\Events\PauseSprintEvent;
use App\Modules\Agile\Events\ResumeSprintEvent;
use App\Modules\Agile\Events\UnlockSprintEvent;
use App\Modules\Agile\Events\UpdateSprintEvent;
use App\Modules\Agile\Http\Requests\SprintChangePriority;
use App\Modules\Agile\Http\Requests\SprintClone;
use App\Modules\Agile\Http\Requests\SprintExport;
use App\Modules\Agile\Http\Requests\SprintIndex;
use App\Modules\Agile\Services\Sprint as SprintService;
use App\Modules\Agile\Services\SprintExportService;
use App\Modules\Agile\Services\SprintIndexService;
use App\Modules\Agile\Services\TicketIndexService;
use Carbon\Carbon;
use Exception;
use App\Helpers\ApiResponse;
use App\Helpers\ErrorCode;
use App\Http\Controllers\Controller;
use App\Models\Db\Project;
use App\Models\Db\Sprint;
use App\Models\Db\Status;
use App\Modules\Agile\Http\Requests\SprintClose;
use App\Modules\Agile\Http\Requests\SprintStoreUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SprintController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param SprintIndex $request
     * @param Project $project
     * @param Activity $model_activity
     * @param SprintIndexService $service
     * @param TicketIndexService $ticketIndexService
     * @return JsonResponse
     */
    public function index(SprintIndex $request, Project $project, Activity $model_activity, SprintIndexService $service, TicketIndexService $ticketIndexService)
    {
        $sprints = $service->getData($request, $project, $model_activity, $ticketIndexService);

        return ApiResponse::transResponseOk(
            $sprints,
            200,
            [
                Sprint::class => SprintWithDetails::class,
                Ticket::class => TicketWithDetails::class,
            ]
        );
    }

    /**
     * Create new sprint.
     *
     * @param SprintStoreUpdate $request
     * @param Project $project
     *
     * @return JsonResponse
     */
    public function store(SprintStoreUpdate $request, Project $project)
    {
        $current_priority = Sprint::where('project_id', $project->id)->max('priority');

        $new_sprint = $project->sprints()->create([
            'name' => trim($request->input('name')),
            'status' => Sprint::INACTIVE,
            'priority' => $current_priority ? $current_priority + 1 : 1,
            'planned_activation' => $request->input('planned_activation'),
            'planned_closing' => $request->input('planned_closing'),
        ]);

        $new_sprint = Sprint::findOrFail($new_sprint->id);

        event(new CreateSprintEvent($project, $new_sprint));

        return ApiResponse::responseOk($new_sprint, 201);
    }

    /**
     * Update sprint.
     *
     * @param SprintStoreUpdate $request
     * @param Project $project
     * @param Sprint $sprint
     *
     * @return JsonResponse
     */
    public function update(SprintStoreUpdate $request, Project $project, Sprint $sprint)
    {
        $data = [
            'name' => trim($request->input('name')),
            'planned_activation' => $request->input('planned_activation'),
            'planned_closing' => $request->input('planned_closing'),
        ];

        $sprint->update($data);

        event(new UpdateSprintEvent($project, $sprint));

        return ApiResponse::responseOk($sprint, 200);
    }

    /**
     * Set active status in specified resource in storage.
     *
     * @param Project $project
     * @param Sprint $sprint
     *
     * @return JsonResponse
     */
    public function activate(Project $project, Sprint $sprint)
    {
        if ($sprint->status != Sprint::INACTIVE) {
            return ApiResponse::responseError(ErrorCode::SPRINT_INVALID_STATUS, 409);
        }
        $sprint->update([
            'status' => Sprint::ACTIVE,
            'activated_at' => Carbon::now(),
        ]);

        event(new ActiveSprintEvent($project, $sprint));

        return ApiResponse::responseOk($sprint, 200);
    }

    /**
     * Set pause status in specified resource in storage.
     *
     * @param Project $project
     * @param Sprint $sprint
     *
     * @return JsonResponse
     */
    public function pause(Project $project, Sprint $sprint)
    {
        if ($sprint->status != Sprint::ACTIVE) {
            return ApiResponse::responseError(ErrorCode::SPRINT_INVALID_STATUS, 409);
        }
        $sprint->update([
            'status' => Sprint::PAUSED,
            'paused_at' => Carbon::now(),
        ]);

        event(new PauseSprintEvent($project, $sprint));

        return ApiResponse::responseOk($sprint, 200);
    }

    /**
     * Resume sprint.
     *
     * @param Project $project
     * @param Sprint $sprint
     *
     * @return JsonResponse
     */
    public function resume(Project $project, Sprint $sprint)
    {
        if ($sprint->status != Sprint::PAUSED) {
            return ApiResponse::responseError(ErrorCode::SPRINT_INVALID_STATUS, 409);
        }
        $sprint->update([
            'status' => Sprint::ACTIVE,
            'resumed_at' => Carbon::now(),
        ]);

        event(new ResumeSprintEvent($project, $sprint));

        return ApiResponse::responseOk($sprint, 200);
    }

    /**
     * Lock sprint.
     *
     * @param Project $project
     * @param Sprint $sprint
     *
     * @return JsonResponse
     */
    public function lock(Project $project, Sprint $sprint)
    {
        $sprint->update([
            'locked' => true,
        ]);

        event(new LockSprintEvent($project, $sprint));

        return ApiResponse::responseOk($sprint, 200);
    }

    /**
     * Unlock sprint.
     *
     * @param Project $project
     * @param Sprint $sprint
     *
     * @return JsonResponse
     */
    public function unlock(Project $project, Sprint $sprint)
    {
        $sprint->update([
            'locked' => false,
        ]);

        event(new UnlockSprintEvent($project, $sprint));

        return ApiResponse::responseOk($sprint, 200);
    }

    /**
     * Set close status in specified resource in storage.
     *
     * @param SprintClose $request
     * @param Project $project
     * @param Sprint $sprint
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function close(SprintClose $request, Project $project, Sprint $sprint)
    {
        if ($sprint->status != Sprint::ACTIVE) {
            return ApiResponse::responseError(ErrorCode::SPRINT_INVALID_STATUS, 409);
        }

        $last_status = Status::lastStatus($project->id);

        DB::transaction(function () use ($request, $project, $sprint, $last_status) {
            if ($last_status) {
                $sprint->tickets()->where('status_id', '!=', $last_status->id)->get()
                    ->each(function ($ticket) use ($request) {
                        $ticket->update(['sprint_id' => $request->input('sprint_id')]);
                    });
            }

            $sprint->update([
                'status' => Sprint::CLOSED,
                'closed_at' => Carbon::now(),
            ]);

            event(new CloseSprintEvent($project, $sprint, $request->input('sprint_id')));
        });

        return ApiResponse::responseOk($sprint, 200);
    }

    /**
     * Delete sprint.
     *
     * @param Project $project
     * @param Sprint $sprint
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy(Project $project, Sprint $sprint)
    {
        if ($sprint->tickets()->count()) {
            return ApiResponse::responseError(ErrorCode::SPRINT_NOT_EMPTY, 409);
        }

        $sprint->delete();

        event(new DeleteSprintEvent($project, $sprint));

        return ApiResponse::responseOk([], 204);
    }

    /**
     * Change priority sprints.
     *
     * @param SprintChangePriority $request
     * @param Project $project
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function changePriority(SprintChangePriority $request, Project $project)
    {
        $last_closed = $project->sprints()->where('status', Sprint::CLOSED)->orderBy('priority', 'desc')->first();

        $current_priority = $last_closed ? $last_closed->priority : 0;

        DB::transaction(function () use ($request, $project, $current_priority) {
            foreach ($request->get('sprints') as $sprint_id) {
                Sprint::where('id', $sprint_id)->update(['priority' => ++$current_priority]);
            }
        });

        event(new ChangePrioritySprintEvent($project));

        return ApiResponse::responseOk();
    }

    /**
     * @param SprintClone $request
     * @param Project $project
     * @param Sprint $sprint
     * @param SprintService $service
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function clone(
        SprintClone $request,
        Project $project,
        Sprint $sprint,
        SprintService $service
    ) {
        $cloned_sprint = $service->cloneSprint($sprint, $request);

        return ApiResponse::responseOk($cloned_sprint, 201);
    }

    public function export(
        Project $project,
        Sprint $sprint,
        SprintExportService $service
    ): BinaryFileResponse {
        $export = $service->makeExport($project, $sprint);

        return Excel::download($export, $export->getFileName() . '.xlsx');
    }
}
