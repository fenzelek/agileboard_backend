<?php

namespace App\Modules\Project\Http\Controllers;

use App\Filters\ProjectUserFilter;
use App\Helpers\ApiResponse;
use App\Helpers\ErrorCode;
use App\Http\Controllers\Controller;
use App\Models\Db\Project;
use App\Models\Db\ProjectUser;
use App\Models\Db\User;
use App\Modules\Project\Events\AssignedEvent;
use App\Modules\Project\Http\Requests\AttachUser;
use App\Modules\Project\Http\Requests\DetachUser;
use App\Modules\Project\Http\Requests\ProjectUser as ProjectUserRequest;
use App\Modules\Project\Services\Projects;

class UserController extends Controller
{
    /**
     * Index users in project.
     *
     * @param Project $project
     * @param ProjectUserFilter $filter
     * @param ProjectUserRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Project $project, ProjectUserFilter $filter, ProjectUserRequest $request)
    {
        $project_users = ProjectUser::where('project_id', $project->id)
            ->filtered($filter)
            ->with('user', 'role')
            ->orderBy('id')->get();

        return ApiResponse::responseOk($project_users, 200);
    }

    /**
     * Attach user to project.
     *
     * @param AttachUser $request
     * @param Project $project
     * @param Projects $service
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(AttachUser $request, Project $project, Projects $service)
    {
        if ($service->checkTooManyUsers(auth()->user()->getSelectedCompanyId(), $project->users()->count())) {
            return ApiResponse::responseError(ErrorCode::PACKAGE_TOO_MANY_USERS, 410);
        }

        $project->users()->attach($request->input('user_id'), [
            'role_id' => $request->input('role_id'),
        ]);

        event(new AssignedEvent($project, User::findOrFail($request->input('user_id'))));

        $project_users = $project->fresh()->users()->find($request->input('user_id'))->pivot;
        $array_response = ['data' => [
                'project_id' => $project_users->project_id,
                'user_id' => $project_users->user_id,
                'created_at' => (string) $project_users->created_at,
                'updated_at' => (string) $project_users->updated_at,
                'role_id' => $project_users->role_id,
            ],
        ];

        return ApiResponse::responseOk($array_response, 201);
    }

    /**
     * Detach user from project.
     *
     * @param DetachUser $request
     * @param Project $project
     * @param int $user_id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(DetachUser $request, Project $project, $user_id)
    {
        $project->users()->detach($user_id);

        return ApiResponse::responseOk([], 204);
    }
}
