<?php

namespace App\Modules\Project\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Project\Http\Requests\PermissionUpdate;
use App\Models\Db\Project;
use Illuminate\Http\JsonResponse;

class ProjectPermissionController extends Controller
{
    /**
     * Display project permissions.
     *
     * @param Project $project
     *
     * @return JsonResponse
     */
    public function show(Project $project)
    {
        return ApiResponse::responseOk($project->permission, 200);
    }

    /**
     * Update project permissions.
     *
     * @param PermissionUpdate $request
     * @param Project $project
     *
     * @return JsonResponse
     */
    public function update(PermissionUpdate $request, Project $project)
    {
        $project->permission->ticket_create = $request->input('ticket_create');
        $project->permission->ticket_update = $request->input('ticket_update');
        $project->permission->ticket_destroy = $request->input('ticket_destroy');

        $project->permission->ticket_comment_create = $request->input('ticket_comment_create');
        $project->permission->ticket_comment_update = $request->input('ticket_comment_update');
        $project->permission->ticket_comment_destroy = $request->input('ticket_comment_destroy');

        $project->permission->owner_ticket_show = $request->input('owner_ticket_show');
        $project->permission->admin_ticket_show = $request->input('admin_ticket_show');
        $project->permission->developer_ticket_show = $request->input('developer_ticket_show');
        $project->permission->client_ticket_show = $request->input('client_ticket_show');

        $project->permission->save();

        return ApiResponse::responseOk($project->permission->fresh(), 200);
    }
}
