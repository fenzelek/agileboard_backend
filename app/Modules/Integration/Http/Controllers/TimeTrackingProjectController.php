<?php

namespace App\Modules\Integration\Http\Controllers;

use App\Filters\TimeTrackingProjectFilter;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Db\Integration\Integration;
use App\Models\Db\Integration\TimeTracking\Project;
use App\Modules\Integration\Http\Requests\TimeTracking\FetchProject;
use App\Modules\Integration\Http\Requests\TimeTracking\UpdateProject;
use App\Modules\Integration\Services\TimeTracking\TrackTime;
use App\Modules\Integration\Services\TimeTracking\UpdateProject as UpdateProjectService;
use App\Services\Paginator;
use App\Modules\Integration\Http\Requests\TimeTracking\Project as ProjectRequest;
use Illuminate\Contracts\Auth\Guard;

class TimeTrackingProjectController extends Controller
{
    /**
     * Get list of projects.
     *
     * @param ProjectRequest $request
     * @param Paginator $paginator
     * @param TimeTrackingProjectFilter $filter
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(
        ProjectRequest $request,
        Paginator $paginator,
        TimeTrackingProjectFilter $filter
    ) {
        return ApiResponse::responseOk($paginator->get(
            Project::filtered($filter),
            'time-tracking-project.index'
        ), 200);
    }

    /**
     * Update project.
     *
     * @param UpdateProject $request
     * @param $time_tracking_project_id
     * @param Guard $auth
     * @param UpdateProjectService $service
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateProject $request, $time_tracking_project_id, Guard $auth, UpdateProjectService $service)
    {
        $project = $service->run($time_tracking_project_id, $request->input('project_id'), $auth->user());

        return ApiResponse::responseOk($project, 200);
    }

    /**
     * Fetch projects from external service.
     *
     * @param FetchProject $request
     * @param TrackTime $track_time
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetch(FetchProject $request, TrackTime $track_time)
    {
        $track_time->fetchProjects(Integration::findOrFail($request->input('integration_id')));

        return ApiResponse::responseOk([], 200);
    }
}
