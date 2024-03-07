<?php

namespace App\Modules\Knowledge\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\KnowledgePageMin;
use App\Http\Resources\RoleMin;
use App\Models\Db\Knowledge\KnowledgeDirectory;
use App\Models\Db\Project;
use App\Models\Db\Knowledge\KnowledgePage;
use App\Models\Db\Role;
use App\Modules\Knowledge\Http\Requests\StoreUpdateDirectoryRequest;
use App\Modules\Knowledge\Services\Knowledge;
use App\Services\Paginator;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KnowledgeDirectoryController extends Controller
{
    /**
     * Show list of directories in project.
     *
     * @param Project $project
     * @param Paginator $paginator
     * @param Knowledge $knowledge
     * @param Guard $auth
     *
     * @return JsonResponse
     */
    public function index(
        Project $project,
        Paginator $paginator,
        Knowledge $knowledge,
        Guard $auth
    ) {
        $directories = $paginator->get(
            $knowledge->directoryList($project, $auth->user()),
            'knowledge-directory.index',
            ['project' => $project]
        );

        return ApiResponse::transResponseOk($directories, 200, [
            KnowledgePage::class => KnowledgePageMin::class,
            Role::class => RoleMin::class,
        ]);
    }

    /**
     * Create directory in Project.
     *
     * @param StoreUpdateDirectoryRequest $request
     * @param Project $project
     * @param Guard $auth
     * @param Knowledge $knowledge
     *
     * @return JsonResponse
     */
    public function store(
        StoreUpdateDirectoryRequest $request,
        Project $project,
        Guard $auth,
        Knowledge $knowledge
    ) {
        $directory = $knowledge->createDirectory($request, $project, $auth->user());

        return ApiResponse::responseOk($directory, 201);
    }

    /**
     * Deletes directory and move pages within.
     *
     * @param Request $request
     * @param Project $project
     * @param KnowledgeDirectory $directory
     * @param Knowledge $knowledge
     *
     * @return JsonResponse
     */
    public function destroy(
        Request $request,
        Project $project,
        KnowledgeDirectory $directory,
        Knowledge $knowledge
    ) {
        $knowledge->deleteDirectory($directory, $request);

        return ApiResponse::responseOk([], 204);
    }

    /**
     * Updates knowledge directory data and permissions.
     *
     * @param StoreUpdateDirectoryRequest $request
     * @param Project $project
     * @param KnowledgeDirectory $directory
     * @param Guard $auth
     * @param Knowledge $knowledge
     *
     * @return JsonResponse
     */
    public function update(
        StoreUpdateDirectoryRequest $request,
        Project $project,
        KnowledgeDirectory $directory,
        Guard $auth,
        Knowledge $knowledge
    ) {
        $directory = $knowledge->updateDirectory($request, $directory, $auth->user());

        return ApiResponse::responseOk($directory, 200);
    }
}
